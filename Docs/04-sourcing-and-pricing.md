# Sourcing and Pricing Deep Dive

This document covers SupplyMars's most distinctive capability: multi-supplier sourcing with dynamic pricing. These features go beyond what most e-commerce tutorials cover, modeling the complexity you'd encounter in a real back-office system.

## Supplier Hierarchy

### Local Warehouse vs. Dropshippers

The system distinguishes between two supplier types:

| Type | `isWarehouse` | Characteristics |
|------|---------------|-----------------|
| **Warehouse** | `true` | Primary inventory, fastest fulfillment, controlled stock |
| **Dropshippers** | `false` | External partners, variable costs, independent stock |

**Warehouse Supplier:**
- Single warehouse per system
- Products are initially created from warehouse catalog
- Provides baseline availability
- Stock is directly managed

**Dropship Suppliers:**
- Multiple dropshippers allowed
- Products may overlap with warehouse
- Costs fluctuate independently
- Stock managed via simulated feeds

### Supplier Entity

```php
// src/Purchasing/Domain/Model/Supplier/Supplier.php

class Supplier
{
    private string $name;
    private bool $isActive;
    private bool $isWarehouse;

    // Collections
    private Collection $supplierProducts;
    private Collection $purchaseOrders;

    public function setAsWarehouse(bool $isWarehouse): void
    {
        $this->isWarehouse = $isWarehouse;
    }
}
```

## Supplier Product Mapping

### How Products Connect

```
┌─────────────────────────────────────────────────────────────────┐
│                              SUPPLIER                           │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │
│  │    Warehouse    │  │  Dropshipper A  │  │  Dropshipper B  │  │
│  └────────┬────────┘  └────────┬────────┘  └────────┬────────┘  │
└───────────┼────────────────────┼────────────────────┼───────────┘
            │                    │                    │
            ▼                    ▼                    ▼
┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐
│  SupplierProduct  │  │  SupplierProduct  │  │  SupplierProduct  │
│ SKU: WH-001       │  │ SKU: DS-A-001     │  │ SKU: DS-B-001     │
│ Cost: £8.50       │  │ Cost: £7.80       │  │ Cost: £8.20       │
│ Stock: 150        │  │ Stock: 45         │  │ Stock: 0          │
│ MfrPartNo: MFR123 │  │ MfrPartNo: MFR123 │  │ MfrPartNo: MFR123 │
└─────────┬─────────┘  └─────────┬─────────┘  └─────────┬─────────┘
          │                      │                      │
          │                      │                      │
          └──────────────────────┼──────────────────────┘
                                 │
                                 ▼
                    ┌─────────────────────────┐
                    │         PRODUCT         │
                    │  MfrPartNo: MFR123      │
                    │  Name: Widget Pro       │
                    │  Cost: £7.80 (lowest)   │
                    │  Stock: 195 (sum)       │
                    │  SellPrice: £11.99      │
                    └─────────────────────────┘
```

### Mapping Process

When a SupplierProduct is mapped to a Product:

1. **Link established:** `SupplierProduct.product` set to Product reference
2. **Event raised:** `SupplierProductPricingWasChangedEvent`
3. **Listener responds:** `SupplierProductPricingWasChanged` listener
4. **Product updates:** Recalculates active source and pricing

```php
// src/Purchasing/Domain/Model/SupplierProduct/SupplierProduct.php

public function assignProduct(?Product $product): void
{
    $previousProductId = $this->product?->getId();
    $this->product = $product;

    // Event carries previous mapping for cascade logic
    $this->raiseDomainEvent(new SupplierProductPricingWasChangedEvent(
        $this->getPublicId(),
        $previousProductId
    ));
}
```

## Cost Selection Rules

### Best Source Algorithm

Products select their cost source via `calculateBestActiveSource()`:

```php
// src/Catalog/Domain/Model/Product/Product.php

private function calculateBestActiveSource(): ?SupplierProduct
{
    // Step 1: Get active supplier products from active suppliers
    $sources = $this->getActiveSupplierProducts();

    // Step 2: Filter for viable sources
    $viable = $sources->filter(function (SupplierProduct $sp) {
        return $sp->hasStock()
            && $sp->hasPositiveCost()
            && $sp->hasActiveSupplier();
    });

    if ($viable->isEmpty()) {
        return null;
    }

    // Step 3: Sort by cost (ASC), then by stock (DESC)
    $sorted = $viable->toArray();
    usort($sorted, function (SupplierProduct $a, SupplierProduct $b) {
        $costComparison = bccomp($a->getCost(), $b->getCost(), 2);
        if ($costComparison !== 0) {
            return $costComparison;
        }
        // If costs equal, prefer higher stock
        return $b->getStock() <=> $a->getStock();
    });

    return $sorted[0] ?? null;
}
```

### Selection Criteria Priority

1. **Must be active:** SupplierProduct.isActive = true
2. **Supplier must be active:** Supplier.isActive = true
3. **Must have stock:** stock > 0
4. **Must have cost:** cost > 0
5. **Lowest cost wins**
6. **Tie-breaker:** Highest stock wins

### Cost Update Cascade

When a SupplierProduct's cost changes:

```
SupplierProduct.updateCost('9.50')
    │
    ├── Sets new cost
    ├── Raises SupplierProductStockWasChangedEvent
    │
    └── Listener: SupplierProductPricingWasChanged
            │
            ├── Product.recalculateActiveSource()
            │       │
            │       └── May switch to different supplier
            │
            └── Product.recalculatePrice()
                    │
                    └── Updates sellPrice, sellPriceIncVat
```

## Stock Availability Rules

### Stock Calculation

Product stock is derived, not stored directly:

```php
// Stock comes from activeProductSource
public function getStock(): int
{
    return $this->activeProductSource?->getStock() ?? 0;
}
```

### Stock Aggregation Options

For fulfillment, the system can:

1. **Single source:** Use best source's stock only
2. **Multi-source:** Aggregate across suppliers (for order splitting)

```php
// src/Catalog/Domain/Model/Product/Product.php

public function getBestSourceWithMinQuantity(int $minQuantity): ?SupplierProduct
{
    foreach ($this->getActiveSupplierProducts() as $sp) {
        if ($sp->getStock() >= $minQuantity
            && $sp->hasPositiveCost()
            && $sp->hasActiveSupplier()) {
            return $sp;
        }
    }
    return null;
}
```

### Stock Simulation

The `app:update-supplier-stock` command simulates real-world inventory:

```php
// src/Purchasing/UI/Console/updateSupplierStockCommand.php

const STOCK_REPLENISH_LEVEL = 20;
const STOCK_VARIANCE_PERCENT = 10;

private function realWorldStockLevelSimulator(SupplierProduct $product): void
{
    if ($product->getStock() <= self::STOCK_REPLENISH_LEVEL) {
        // Low stock: replenish (0-100 units)
        $this->replenishStock($product);
    } else {
        // Normal: decrease by up to 10%
        $this->decreaseStock($product);
    }
}
```

## Category/Subcategory Multipliers

### Markup Hierarchy

Markup percentages cascade through three levels:

```
Product.defaultMarkup (if > 0)
    │
    │ (inherits if 0)
    ▼
Subcategory.defaultMarkup (if > 0)
    │
    │ (inherits if 0)
    ▼
Category.defaultMarkup (always > 0, typically 5%)
```

### Price Model Hierarchy

Same pattern for price model (pretty rounding):


## Pretty-Price Rounding

### Price Model Enum

```php
// src/Shared/Domain/ValueObject/PriceModel.php

enum PriceModel: string
{
    case NONE = 'none';          // No model (for inheritance)
    case DEFAULT = 'default';     // Cost+ (no rounding)
    case PRETTY_00 = 'pretty00';  // Round to .00
    case PRETTY_10 = 'pretty10';  // Round to .10
    case PRETTY_49 = 'pretty49';  // Round to .49 or .99
    case PRETTY_95 = 'pretty95';  // Round to .95
    case PRETTY_99 = 'pretty99';  // Round to .99
}
```

### Calculation Flow

```php
// src/Shared/Domain/Service/Pricing/MarkupCalculator.php

public function calculatePrettyPrice(
    string $cost,
    string $markup,
    string $vatRate,
    PriceModel $priceModel
): string {
    // Step 1: Apply markup
    $withMarkup = $this->calculateSellPrice($cost, $markup);

    // Step 2: Apply VAT
    $vatMultiplier = $this->getVatMultiplier($vatRate);
    $withVat = bcmul($withMarkup, $vatMultiplier, 8);

    // Step 3: Apply pretty rounding
    return $priceModel->getPrettyPrice($withVat);
}
```

## Why Order Items Can Have Multiple Prices

### Price Capture at Order Time

When a CustomerOrderItem is created, it captures the product's current price:

```php
// src/Order/Domain/Model/Order/CustomerOrderItem.php

public static function createFromProduct(
    CustomerOrder $customerOrder,
    Product $product,
    int $quantity
): static {
    $item = new static();
    $item->customerOrder = $customerOrder;
    $item->product = $product;
    $item->quantity = $quantity;

    // Price is captured NOW, not looked up later
    $item->price = $product->getSellPrice();
    $item->priceIncVat = $product->getSellPriceIncVat();
    $item->weight = $product->getWeight();

    return $item;
}
```

### Scenarios for Different Prices

**Scenario 1: Price changed between line additions**
```
10:00 - Customer adds Widget Pro (£11.99) × 2
10:15 - Supplier cost drops, price recalculates to £10.99
10:30 - Customer adds Widget Pro (£10.99) × 1

Order contains:
  Line 1: Widget Pro × 2 @ £11.99 = £23.98
  Line 2: Widget Pro × 1 @ £10.99 = £10.99
  Total: £34.97
```

**Scenario 2: Admin modifies line price**
```
Order Line: Widget Pro × 5 @ £11.99
Admin applies discount: Updates price to £9.99

Result: Widget Pro × 5 @ £9.99 = £49.95
```

**Scenario 3: Split fulfillment with different supplier costs**
```
Order Line: Widget Pro × 10 @ £11.99 (customer price)

Allocation:
  PO to Warehouse: × 6 @ £8.50 (supplier cost)
  PO to Dropshipper: × 4 @ £7.80 (supplier cost)

Customer pays: 10 × £11.99 = £119.90
Cost to business: (6 × £8.50) + (4 × £7.80) = £82.20
Gross margin: £37.70
```

## Why Quantities Can Be Split Across Suppliers

### The Splitting Problem

When a customer orders 100 units but no single supplier has full stock:

| Supplier | Stock | Cost |
|----------|-------|------|
| Warehouse | 45 | £8.50 |
| Dropshipper A | 30 | £7.80 |
| Dropshipper B | 50 | £8.20 |

**Allocation result:**
1. Dropshipper A: 30 units (lowest cost, depleted)
2. Warehouse: 45 units (next best, depleted)
3. Dropshipper B: 25 units (remaining need)

### Outstanding Quantity Tracking

```php
// src/Order/Domain/Model/Order/CustomerOrderItem.php

public function getOutstandingQty(): int
{
    return max($this->quantity - $this->getQtyAddedToPurchaseOrders(), 0);
}

public function getQtyAddedToPurchaseOrders(): int
{
    return $this->purchaseOrderItems
        ->filter(fn(PurchaseOrderItem $poi) =>
            !$poi->getStatus()->isCancelled() &&
            !$poi->getStatus()->isRefunded()
        )
        ->reduce(fn(int $sum, PurchaseOrderItem $poi) =>
            $sum + $poi->getQuantity(), 0
        );
}
```

## Worked Examples

### Example 1: Simple Single-Source Order

**Setup:**
- Product: Mars Helmet
- Category markup: 25%
- VAT: 20%
- Price model: PRETTY_99
- Warehouse cost: £45.00
- Warehouse stock: 100

**Calculation:**
```
Base cost: £45.00
With markup: £45.00 × 1.25 = £56.25
With VAT: £56.25 × 1.20 = £67.50
Pretty rounded: £67.99 (PRETTY_99)
```

**Order:**
- Customer orders 5 × Mars Helmet @ £67.99
- Single PO to Warehouse for 5 units @ £45.00 cost

### Example 2: Multi-Supplier Split

**Setup:**
- Product: Oxygen Tank
- Warehouse: 15 units @ £120.00
- Dropshipper A: 30 units @ £115.00
- Customer orders: 25 units

**Allocation:**
```
Outstanding: 25

Step 1: Best source = Dropshipper A (lowest cost)
  - Allocate: 25 units (has 30)
  - PO created for Dropshipper A: 25 × £115.00

Outstanding: 0
Allocation complete.
```

**Alternative scenario (if Dropshipper A only had 10):**
```
Outstanding: 25

Step 1: Best source = Dropshipper A (10 available, lowest cost)
  - Allocate: 10 units
  - PO created for Dropshipper A: 10 × £115.00

Outstanding: 15

Step 2: Best source = Warehouse (15 available, next lowest)
  - Allocate: 15 units
  - PO created for Warehouse: 15 × £120.00

Outstanding: 0
Allocation complete.
```

### Example 3: Price Change Impact

**Before price change:**
```
Widget Pro:
  - Warehouse cost: £8.50
  - Category markup: 30%
  - VAT: 20%
  - Price model: DEFAULT

Sell price: £8.50 × 1.30 × 1.20 = £13.26
```

**Supplier updates cost to £7.00:**
```
1. SupplierProduct.updateCost('7.00')
2. Event: SupplierProductStockWasChangedEvent
3. Listener: SupplierProductPricingWasChanged
4. Product.recalculatePrice()

New sell price: £7.00 × 1.30 × 1.20 = £10.92
```

**Impact on existing orders:**
- Orders already placed keep their original price (£13.26)
- New orders get the updated price (£10.92)

### Example 4: Subcategory Override

**Hierarchy:**
```
Category: Electronics
  - defaultMarkup: 20%
  - priceModel: PRETTY_99

  Subcategory: Premium Electronics
    - defaultMarkup: 35%
    - priceModel: PRETTY_00

    Product: Gold-Plated Connector
      - defaultMarkup: 0 (inherits)
      - priceModel: NONE (inherits)
```

**Resolution:**
- Active markup: 35% (from Subcategory)
- Active price model: PRETTY_00 (from Subcategory)

**Calculation (cost = £5.00, VAT = 20%):**
```
With markup: £5.00 × 1.35 = £6.75
With VAT: £6.75 × 1.20 = £8.10
Pretty rounded: £9.00 (PRETTY_00)
```

### Example 5: Category Change Cascade

**Scenario:** Category markup changes from 20% to 25%

**Affected products (markup target = CATEGORY):**
```
Category → Recalculate all

But filtered by:
- Products where getActiveMarkupTarget() === 'CATEGORY'
- Products with their own defaultMarkup > 0: SKIP
- Products in subcategories with defaultMarkup > 0: SKIP
```

**Smart cascade:**
```php
// Only recalculates necessary products
foreach ($category->getActiveProducts() as $product) {
    if ($product->getActiveMarkupTarget() === 'CATEGORY') {
        $product->recalculatePrice($this->markupCalculator);
    }
}
```

This selective approach prevents unnecessary recalculations when products or subcategories have their own markup overrides.
