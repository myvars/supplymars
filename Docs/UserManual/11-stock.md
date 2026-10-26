# Stock

Stock is held per supplier product: each supplier has its own stock and cost for the products it sells. A product's stock and cost come from one of them, its **active source**.

Press `?` on this screen for a short summary.

**Where:** **Catalog > Products**, then open a product and its **Stock** tab.

## The Stock tab

**Inventory card** (top):

- **Cost Price** (with the active source's supplier), **Stock** (with when that supplier product was last updated), **Sell Price** (with the price model) and **Sell Inc VAT** (with the VAT rate name).
- **Mark-up**, with where the VAT, price model and mark-up come from (see [Pricing and VAT](12-pricing.md)).
- An incomplete product shows the heading "Incomplete Product" with "No active supplier", or "Inactive category, subcategory or product", instead ([Products](03-products.md#rules-and-limits)).

**Supplier Sources** (below), one row per supplier product mapped to this product:

- Supplier name (links to the supplier product), product name, product code and when it was last updated.
- "N in stock" or "No stock", the cost, and how much more it costs than the active source (for example +£1.20).
- The active source has a green bar and a **Best price** badge. An inactive supplier product shows an Inactive badge. An active one whose supplier is inactive shows "Supplier inactive".

## Tasks

### Turn a source on or off

Click the toggle icon on the row. An inactive source is never chosen as the active source or by automatic allocation. The product's active source, cost and price are worked out again straight away.

### Remove a source

1. Click the Remove (trash) icon on the row.
2. Click **Remove**.

The supplier product is unmapped from this product, not deleted. It stays in **Purchasing > Supplier Products**. The product picks its active source again from what is left.

### Change a supplier's stock or cost

1. Go to **Purchasing > Supplier Products**. Sort by **Stock**, or filter on **Stock Level: Out of Stock**, to find low stock.
2. Click the pencil icon on the supplier product.
3. Change **Stock Level** (0 to 10,000) or **Cost**.
4. Click **Update Supplier Product**.

The mapped product's active source, stock, cost and price are worked out again, and the change is logged. This happens only when a value changed: saving the form unchanged recalculates nothing.

### See stock history

Open the product's **History** tab. It has **Stock History** and **Cost History** charts, with **7 days**, **30 days** (the default), **90 days** and **All** buttons. Every stock or cost change of each supplier product is logged, from edits and from the stock simulation.

## How the active source is chosen

1. Only supplier products that are active, with an active supplier.
2. Only those with stock and a cost above 0.
3. The lowest cost wins. On equal cost, the one with more stock wins.

If none qualifies, the product has no active source: it shows "No active supplier", its stock shows 0, and it keeps its last cost and price.

Automatic allocation (the order's **Allocate** button and the scheduled job) uses the same rules, but asks for a supplier with stock of at least the item's whole outstanding quantity. If none has enough, the item is left unallocated; it is never split across suppliers. Manual allocation from the order page lets you pick any active source, whatever its stock ([Order Items](09-order-items.md#allocate-an-item-to-a-supplier)). The full rules, with a worked example, are in [Sourcing and Pricing](../04-sourcing-and-pricing.md) (technical).

## Rules and limits

- **Stock is never reserved or deducted.** Allocation, acceptance, shipping and delivery leave supplier stock as it is. Stock changes only when someone edits a supplier product, or when the stock simulation runs. Many orders can be allocated against the same stock.
- **Simulated stock changes update the product.** When the stock simulation changes a supplier product's stock or cost, the product picks its active source again and is repriced, as after an edit.
- **To bring a product up to date** after a change that did not reach it (data from before simulated changes were applied), click the toggle on one of its sources twice (off, then on again), or change a value on a supplier product and save. Saving a supplier product with nothing changed does not recalculate anything.
- Turning a supplier on or off in **Purchasing > Suppliers** also recalculates the products it supplies ([Suppliers](05-suppliers.md)).
- The Stock tab never shows lead time. It shows on the supplier product's own page, and in the order item's supplier list for a supplier product with no stock.

## Related

- [Supplier Products](06-supplier-products.md): supplier products and mapping
- [Pricing and VAT](12-pricing.md): how cost becomes a sell price
- [Products](03-products.md)
- [Simulations](14-simulations.md): what the stock simulation does
