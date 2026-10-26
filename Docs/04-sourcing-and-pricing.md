# Sourcing and Pricing

How a product picks its supplier, how order lines become purchase order items, how statuses roll up, and how a sell price is worked out. Read it before changing `Product`, the allocators, the order/PO status code, the Pricing listeners or `MarkupCalculator`. The reasons behind the design are in ADRs [001](adr/001-multi-supplier-sourcing.md), [002](adr/002-order-line-splitting.md) and [004](adr/004-pricing-abstraction.md).

## Rules

### Suppliers and supplier products

- A `Product` has many `SupplierProduct`s, one per supplier that sells it. Order items point at the product. PO items point at the supplier product.
- `Supplier::isWarehouse` is set only by `SupplierFactory::asWarehouse()` (fixtures). The supplier form has no such field and nothing limits it to one supplier. Only the two setup commands read it (`CreateWarehouseProductsCommand`, and `CreateSupplierProductsCommand` through `findNonWarehouseSupplier()`). Sourcing gives the warehouse no preference.
- `SupplierProductMappingService` maps a supplier product to a product by product **name**. `mfrPartNumber` is not unique and is not used for matching.

### Best source

`Product::calculateBestActiveSource()` goes through the supplier products:

1. Only active supplier products whose supplier is also active (`getActiveSupplierProducts()`).
2. Skip any with stock ≤ 0, stock below the quantity asked for, or cost ≤ 0.
3. Lowest cost wins (`bccomp` to 2 places). On equal cost, the one with more stock wins.

It is used two ways:

| Caller | Quantity asked for | Result |
|---|---|---|
| `recalculateActiveSource()` | 1 | the product's **active source**. Its cost, stock and lead time are copied onto the product, and the price is recalculated |
| `getBestSourceWithMinQuantity($qty)` (allocation) | the line's whole outstanding quantity | the supplier product a PO item is created on, or `null` |

### Allocation

`OrderAllocator::process()` runs from `app:build-purchase-orders`, the Allocate button (`app_order_allocate`, `api_order_allocate`) and `app:refund-purchase-orders`.

- For each line with outstanding quantity, it asks for one source with stock for the **whole** outstanding quantity. If none has it, the line is skipped and stays outstanding. **Automatic allocation never splits a line.**
- The PO item goes on the order's editable (PENDING) PO for that supplier, or a new one (`EditablePurchaseOrderProvider`). A second PO item for the same line on the same PO is not created. The existing one grows to its maximum instead (`OrderItemAllocator`).
- PO items are created PENDING. They move to PROCESSING only once **every** line on the order has nothing outstanding.
- Supplier stock is read live and never reserved or reduced by allocation, shipping or delivery. Only the stock commands and editing a supplier product change it (see [Stock](#stock)).

A line ends up split across suppliers in two ways:

- **By staff.** Lower a PENDING PO item's quantity (`UpdatePurchaseOrderItemQuantityHandler`; 0 removes it), then add a PO item on another supplier product from the order screen (`CreatePurchaseOrderItemHandler`). That puts the whole remaining outstanding quantity on the chosen supplier, with no stock check.
- **After a refund.** A rejected PO item that is refunded no longer counts. The refund command then reallocates the line, using the same one-source rule.

**Outstanding quantity** = line quantity − the quantity on its PO items that are not CANCELLED or REFUNDED (`CustomerOrderItem::getOutstandingQty()`). A PO item's quantity can't go above its current quantity plus the line's outstanding quantity (`PurchaseOrderItem::getMaxQuantity()`). The line's quantity can't be edited below what is on POs.

### Statuses

A PO item may move only as `PurchaseOrderStatus::canTransitionTo()` allows:

| From | To |
|---|---|
| PENDING | PROCESSING, CANCELLED |
| PROCESSING | PENDING (rewind), ACCEPTED, REJECTED, CANCELLED |
| ACCEPTED | REJECTED, SHIPPED |
| REJECTED | REFUNDED |
| SHIPPED | DELIVERED |
| REFUNDED, DELIVERED, CANCELLED | (final) |

Each PO item status change makes its parents derive their status again:

| Status of | Derived as |
|---|---|
| Purchase order | its items' least advanced status, by `getLevel()` (PENDING 1 … DELIVERED 7, CANCELLED 8) |
| Order item | CANCELLED stays CANCELLED. No PO items → PENDING. Some quantity on POs and some outstanding → PROCESSING. Otherwise the least advanced status of its non-refunded PO items, mapped by `OrderStatus::getMappedOrderStatusFromPurchaseOrder()` (PROCESSING, ACCEPTED, REJECTED and REFUNDED all → PROCESSING). All PO items refunded → PENDING |
| Order | its items' least advanced status (PENDING 1 … DELIVERED 4, CANCELLED 5), so it is CANCELLED only when every item is |

### Prices on orders

- An order item copies the product's `sellPrice` and `sellPriceIncVat` when it is added (`CustomerOrderItem::createFromProduct()`). Later price changes leave it alone, so one product can be on an order at two prices.
- Staff edit an item's price inc VAT. The ex-VAT price is worked back with the item's VAT rate (`UpdateOrderItemHandler`).
- A PO item copies the supplier product's cost at allocation into both `price` and `priceIncVat`. No VAT is added.

### Stock

- `Product::$stock`, `$cost` and `$leadTimeDays` are stored copies of the active source's values. They are not a sum over suppliers, and not computed on read.
- They change only when `recalculateActiveSource()` runs, through the Pricing listeners below: when a supplier product is created, edited, mapped or toggled, when its stock or cost changes, or when a supplier is toggled.
- `app:update-supplier-stock` and `app:reset-supplier-stock` call `SupplierProduct::updateStock()` / `updateCost()`. A change raises `SupplierProductStockWasChangedEvent` (the audit log, `SupplierStockChangeLogger`) and, for a mapped supplier product, `SupplierProductPricingWasChangedEvent`, so the product's active source and price follow. `update()` uses private helpers and raises its one pricing event itself. What the commands do is in [09-cli-reference.md](09-cli-reference.md).
- Products that went stale before that (or after any change made outside the entities, such as SQL) are brought back in step by `app:resync-product-pricing`.

### Pricing cascade

All listeners are in `src/Pricing/Application/Listener/` and run synchronously.

| Event (raised when) | Listener | Recalculates |
|---|---|---|
| `SupplierProductPricingWasChangedEvent` (create, `update()` changing supplier, stock, lead time, cost, product or active, or `updateStock()` / `updateCost()` on a mapped supplier product) | `SupplierProductPricingWasChanged` | new mapping: adds to the product. Unmapped: removes from the old product. Moved: removes from the old product, flushes, adds to the new one. Same product: active source. Each runs `recalculateActiveSource()` |
| `SupplierProductStatusWasChangedEvent` (toggle, mapped products only) | `SupplierProductStatusWasChanged` | the mapped product's active source |
| `SupplierStatusWasChangedEvent` | `SupplierStatusWasChanged` | activated: every mapped product. Deactivated: only products whose active source is this supplier's |
| `CategoryPricingWasChangedEvent` (VAT rate, markup, price model or active changed) | `CategoryPricingWasChanged` | the category's active products: all of them for a VAT change, otherwise those whose markup or price model comes from the category |
| `SubcategoryPricingWasChangedEvent` (markup, price model or active changed) | `SubcategoryPricingWasChanged` | the subcategory's active products whose markup or price model comes from the subcategory or the category |
| `VatRateWasChangedEvent` (rate changed) | `VatRateWasChanged` | every product, active or not, in every category using the rate |

Product markup and price model changes raise no event. `Product::changePricing()` and `update()` call `recalculatePrice()` directly.

### Effective markup and price model

- **Markup:** the product's `defaultMarkup` if > 0, else the subcategory's if > 0, else the category's (`Product::getActiveMarkup()`). The first non-zero level **replaces** the others. Markups are never added together.
- **Price model:** the product's if not `NONE`, else the subcategory's if not `NONE`, else the category's (`getActivePriceModel()`).
- Defaults: category 5.000 and `DEFAULT`. Subcategory and product 0.000 and `NONE`. A category can't have `NONE` (constraint and `Category::applyPriceModel()`). Any markup can be 0 (`PositiveOrZero`).
- `getActiveMarkupTarget()` / `getActivePriceModelTarget()` return `PRODUCT`, `SUBCATEGORY` or `CATEGORY`. The listeners use them to skip products whose value comes from another level.

### Price calculation

`Product::recalculatePrice()`, using `MarkupCalculator` (bcmath throughout, half-up rounding: 2 places for prices, 3 for markup; the VAT multiplier is cut to 4 places):

1. Sell price ex VAT = cost × (1 + markup/100).
2. Inc VAT = that × (1 + VAT rate/100). The VAT rate is the category's.
3. The price model rounds the **inc-VAT** price. This is `sellPriceIncVat`.
4. The markup that gives that price is worked back from it (`calculateCustomMarkup()`) and stored as `markup`. `sellPrice` is then cost × (1 + `markup`/100), so it can differ by a penny from the inc-VAT price divided by the VAT multiplier. So `defaultMarkup` is what staff set and `markup` is what the rounded price actually earns.

`PriceModel` (`src/Shared/Domain/ValueObject/PriceModel.php`). Each case's value is its name, e.g. `'PRETTY_00'`. Rounding never goes down:

| Case | Rule | 25.67 → | 25.97 → |
|---|---|---|---|
| `NONE` | no rounding (means "inherit" on product and subcategory) | 25.67 | 25.97 |
| `DEFAULT` | no rounding ("Cost+") | 25.67 | 25.97 |
| `PRETTY_00` | up to the next whole pound | 26.00 | 26.00 |
| `PRETTY_10` | up to the next 10p | 25.70 | 26.00 |
| `PRETTY_49` | .49 if the pence are below 50, else .99 | 25.99 | 25.99 |
| `PRETTY_95` | .95, or the next pound's .95 above .95 | 25.95 | 26.95 |
| `PRETTY_99` | always .99 | 25.99 | 25.99 |

## Worked example

A product in a category with markup 25%, VAT 20% and `PRETTY_99`. The product and subcategory have markup 0 and `NONE`. Supplier products (all active, active suppliers):

| Supplier product | Cost | Stock |
|---|---|---|
| A | 46.00 | 10 |
| B | 45.00 | 3 |
| C | 45.00 | 8 |
| D | 40.00 | 0 |

- **Active source:** C. D has no stock. B and C tie on cost and C has more stock. The product stores cost 45.00 and stock 8.
- **Price:** 45.00 × 1.25 = 56.25. × 1.20 = 67.50. `PRETTY_99` gives `sellPriceIncVat` 67.99. Worked back: `sellPrice` 56.66, `markup` 25.911.
- **Allocation:** a line for 5 goes to C. A line for 9 goes to A, the only source with 9, at 46.00. A line for 11 is skipped and stays outstanding.

## Gotchas

- **Allocate can fail and still save.** `AllocateOrderHandler` flushes what it could allocate, then returns "Cannot allocate all items" if any line is still outstanding. Those PO items stay PENDING.
- **A product with no viable source keeps its last cost and price.** `removeActiveSource()` only clears the source and sets stock to 0.
- **A product never sourced is not priced.** Its cost is 0.00, and `recalculatePrice()` returns without changing the price. `MarkupCalculator` still throws on a cost of 0, so call it through `recalculatePrice()`.
- **An effective markup of 0 sells at cost.** The price is cost plus VAT and `markup` is 0.000. Rounding only goes up, so the worked-back price is never below cost; `calculateMarkupFromSellPrice()` throws only if it is.
- **The stock commands flush once per change.** Each `updateStock()` or `updateCost()` on a mapped supplier product raises its own pricing event, and the listener flushes for each. A replenished product (stock and cost) is recalculated twice; the result is the same.
- **Moving a supplier product between products needs the extra flush.** The active source is unique and Doctrine orders UPDATEs by id, so the listener flushes the old product first (comment in `SupplierProductPricingWasChanged`). Keep it.
- **Tests reprice too.** `SupplierProductFactory` adds the supplier product to its product, and its pricing event fires on flush. So a factory-made product has an active source, cost and price.

## Related

- [ADR 001](adr/001-multi-supplier-sourcing.md), [ADR 002](adr/002-order-line-splitting.md), [ADR 004](adr/004-pricing-abstraction.md): why it works this way
- [05-features.md](05-features.md): business rules per screen
- [09-cli-reference.md](09-cli-reference.md): the allocation, refund and stock commands
- [UserManual/12-pricing.md](UserManual/12-pricing.md): pricing for staff users
