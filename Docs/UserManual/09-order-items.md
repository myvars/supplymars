# Order Items

## What Order Items Are For

Order items are the individual line items within a customer order. Each order item represents:

- A specific product being ordered
- The quantity requested
- The agreed price at time of order
- The allocation status for supplier fulfillment

Order items are the link between customer orders and purchase orders.

## What You Can Do

- View order item details
- Edit item quantity and price
- Cancel order items
- Add items to purchase orders
- View supplier allocation options

## Screens and Actions

### View Order Item Details

**Navigate to:** Order detail > Click on an order item

The item detail page shows:
- Product image and name
- Unit price (with and without VAT)
- Quantity ordered vs. outstanding
- Current status
- Related purchase order items

### Edit an Order Item

**Navigate to:** Order item detail > Click **Edit**

You can update:
- **Quantity** - Total units ordered
- **Price Inc VAT** - Override unit price

**Restrictions:**
- Quantity cannot be reduced below the amount already allocated to purchase orders
- Only items in PENDING or PROCESSING status can be edited

### Cancel an Order Item

**Navigate to:** Order item detail > Click **Cancel**

**Requirements:**
- No quantity allocated to purchase orders
- Item status must be PENDING or PROCESSING

After cancellation:
- Item status changes to CANCELLED
- Item is excluded from order totals
- Order status may update based on remaining items

### Add to Purchase Order

**Navigate to:** Order item detail or Order detail > Click supplier option

When an item has outstanding quantity (not yet allocated), you can manually allocate it:

1. View available supplier options
2. Click on a supplier product to add to a PO
3. Specify quantity to allocate
4. System creates or adds to a purchase order

## Fields and Options

### Order Item Fields

| Field | Editable | Description |
|-------|----------|-------------|
| Product | No | The product being ordered |
| Quantity | Yes | Total units ordered (1-10,000) |
| Price | No | Unit price without VAT |
| Price Inc VAT | Yes | Unit price with VAT |
| Weight | No | Unit weight in grams |
| Status | No | Current item status |
| Outstanding Qty | No | Quantity not yet in purchase orders |

### Calculated Values

| Value | Calculation |
|-------|-------------|
| Total Price | Quantity × Unit Price |
| Total Price Inc VAT | Quantity × Unit Price Inc VAT |
| Total Weight | Quantity × Unit Weight |
| Allocated Qty | Quantity - Outstanding Qty |

## Status and Lifecycle

### Item Status

| Status | Meaning |
|--------|---------|
| **PENDING** | Not yet allocated to suppliers |
| **PROCESSING** | Allocated to purchase orders |
| **SHIPPED** | Items dispatched by supplier(s) |
| **DELIVERED** | Items delivered |
| **CANCELLED** | Item cancelled |

### Status Determination

Item status is automatically derived from its purchase order items:
- Takes the lowest-level status from all related PO items
- Example: If PO items are [ACCEPTED, SHIPPED], item is ACCEPTED

### Outstanding Quantity

Outstanding quantity tracks how many units still need allocation:

```
Outstanding = Total Quantity - (Sum of PO Item Quantities)
```

Only non-cancelled, non-refunded PO items count toward allocation.

## Supplier Allocation

### Viewing Options

On the order item detail or order page, you can see:
- Available supplier products for this product
- Each supplier's cost and stock level
- Which supplier is the active/preferred source

### Manual Allocation

To manually allocate an item to a supplier:

1. Find the item with outstanding quantity
2. Click the supplier product option
3. The system creates a purchase order item
4. Outstanding quantity decreases
5. Item status updates to PROCESSING

### Automatic Allocation

Use the order-level "Allocate" button to automatically:
1. Find best suppliers for all items
2. Create purchase orders
3. Allocate all outstanding quantities

## Quantity Management

### Editing Quantity

When editing an item's quantity:

- **Increasing:** Adds to outstanding quantity for allocation
- **Decreasing:** Only allowed if new quantity ≥ allocated quantity
- **Setting to 0:** Not allowed; cancel the item instead

### Allocation Constraints

| Scenario | Behavior |
|----------|----------|
| Quantity = 10, Allocated = 0 | Can edit to any value 1-10,000 |
| Quantity = 10, Allocated = 5 | Can only edit to 5 or higher |
| Quantity = 10, Allocated = 10 | Cannot reduce quantity |

## Integration with Purchase Orders

### One-to-Many Relationship

A single order item can be fulfilled by multiple purchase order items:
- Different suppliers may fulfil portions
- Partial shipments create multiple PO items
- Each PO item tracks its own status

### Status Cascade

When PO item status changes:
1. PO item updates (e.g., SHIPPED)
2. Order item recalculates its status
3. Customer order recalculates its status

## Warnings

- Cannot cancel items that have been allocated to purchase orders
- Reducing quantity below allocated amount is not possible
- Price changes do not affect existing purchase orders
- Cancelled items cannot be uncancelled
- Item deletion is not supported; use cancellation instead
- Manual allocation bypasses the automatic supplier selection logic
