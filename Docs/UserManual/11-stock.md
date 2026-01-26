# Stock Management

## What Stock Management Is For

Stock management tracks inventory levels across your suppliers. In SupplyMars, stock is managed at the supplier product level - each supplier maintains their own stock for the products they offer.

Key concepts:
- **Supplier product stock** - Units available at each supplier
- **Active source** - The supplier automatically selected for orders
- **Lead time** - Days until stock is available if out of stock

## What You Can Do

- View stock levels for products
- View all supplier options for a product
- Update supplier product stock levels
- Toggle supplier products active/inactive
- Remove supplier products from catalog products
- View stock audit history

## Screens and Actions

### Product Stock Dashboard

**Navigate to:** Product detail > **Stock** tab

This is the main stock management screen showing:

**Inventory Card:**
- Current cost price (from active supplier)
- Stock level
- Sell price (exc and inc VAT)
- Markup percentage
- VAT rate and price model
- Last update timestamp

**Supplier Product Cards:**
For each supplier offering this product:
- Supplier name (with inactive indicator if applicable)
- Product name and code
- Cost price
- Stock level or lead time
- Active/Inactive status toggle
- Remove button (X)
- Green border on the active source

### Update Stock Levels

**Navigate to:** Suppliers > Supplier Products > Find product > Click **Edit**

1. Update the **Stock Level** field (0-10,000)
2. Click **Save**

Changes take effect immediately:
- Audit log records the change
- Product's active source may recalculate
- Related products update their displayed stock

### Toggle Supplier Product Status

**Navigate to:** Product Stock dashboard > Click status toggle on supplier card

Quickly switch a supplier product between Active and Inactive:
- **Active:** Can be selected for purchase orders
- **Inactive:** Won't be used for new orders

### Remove Supplier Product

**Navigate to:** Product Stock dashboard > Click **X** on supplier card

1. Confirm the removal
2. Supplier product is unlinked (not deleted)
3. Product recalculates pricing with remaining suppliers

### View in Supplier Product List

**Navigate to:** Suppliers > Supplier Products

The list shows stock levels for all supplier products:
- Stock column shows current levels
- Sort by Stock to find low/out of stock items
- Filter by "Out of Stock" to find items needing attention

## Stock Display

### Stock Status Indicators

| Display | Meaning |
|---------|---------|
| **X in stock** | Has available inventory |
| **Out of stock** | Zero units available |
| **Out of stock (Y day lead time)** | No stock, shows expected wait |

### Where Stock Appears

- Product list cards
- Product detail pages
- Product stock dashboard
- Supplier product list
- Supplier product detail pages

## Active Source Selection

When a product has multiple supplier options, the system automatically selects the "active source":

**Selection criteria (in order):**
1. Must have stock > 0
2. Must have cost > 0
3. Must be active (supplier product)
4. Must have active supplier
5. Lowest cost wins
6. Highest stock breaks ties

**When selection recalculates:**
- Supplier product stock changes
- Supplier product cost changes
- Supplier product status changes
- Supplier status changes
- Supplier product removed from product

## Stock Audit Trail

Every stock change is logged in the audit system:

**Logged information:**
- Event type (stock changed, cost changed)
- Supplier product ID
- Final stock level
- Final cost
- Timestamp

This audit trail helps you:
- Track stock history
- Identify patterns
- Investigate discrepancies

## Stock and Orders

### Impact on Orders

Stock levels affect order processing:
- Products with no stock cannot be allocated
- Lead time indicates when stock may be available
- Alternative suppliers may be selected if primary is out of stock

### Stock Reservation

SupplyMars does **not** reserve stock when orders are created. Stock is deducted when:
- Purchase orders are created (allocation)
- Suppliers confirm they can fulfil

This means:
- Stock levels show current availability
- Multiple orders may compete for the same stock
- Allocation may fail if stock depletes

## Warnings

- Stock is managed per supplier product, not per catalog product
- Changing stock may trigger active source recalculation
- Setting a supplier product to inactive may change the active source
- Removing all suppliers from a product leaves it unfulfillable
- Stock changes are audited and cannot be hidden
- Lead time only displays when stock is zero
