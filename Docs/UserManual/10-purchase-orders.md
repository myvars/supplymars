# Purchase Orders

## What Purchase Orders Are For

Purchase Orders (POs) are orders placed with suppliers to fulfil customer orders. When a customer order is allocated, the system creates one or more purchase orders:

- One PO per supplier involved in fulfilling the order
- Each PO contains items from the customer order
- POs progress through their own status lifecycle
- PO status feeds back to update customer order status

## What You Can Do

- View all purchase orders with filtering
- View purchase order details
- Edit item quantities (when pending)
- Change item statuses (accept, reject, ship, deliver)
- Track fulfilment progress

## Screens and Actions

### Purchase Order List

**Navigate to:** Purchase Orders

The list shows all POs with:
- PO number (#000001 format)
- Related customer order number
- Supplier name
- Customer name
- Created date
- Total price (including VAT)
- Current status

**Sorting options:** PO ID, Created Date, Customer Order ID, Total Price, Status

**Filtering options:**
- By Supplier
- By PO ID
- By Customer Order ID
- By Customer ID
- By Product ID
- By Status
- By Date Range

### View Purchase Order Details

**Navigate to:** Click on any purchase order

The detail page shows:

**PO Card:**
- PO number and supplier name
- Created date
- Link to related customer order
- Current status (clickable for history)

**Summary Card:**
- Delivery address
- Items subtotal
- Shipping cost
- Total before VAT
- VAT amount
- Grand total

**Items Card:**
- Count of line items
- For each item:
  - Product image
  - Supplier product name
  - Unit price (inc VAT)
  - Quantity
  - Status (clickable for history)
  - Delivered date (if delivered)
  - Edit button (if editable)

### Edit Item Quantity

**Navigate to:** PO detail > Click item's **Edit** button > Edit Quantity

1. Enter new quantity (0-10,000)
2. Click **Save**

**Special cases:**
- Setting quantity to 0 removes the item
- If all items removed, the entire PO is deleted
- Maximum quantity limited by customer order item's outstanding amount

**Requirements:** PO must be in PENDING status.

### Change Item Status

**Navigate to:** PO detail > Click item's **Edit** button > Edit Status

1. Select new status from dropdown
2. Click **Save**

Only valid status transitions are allowed (see Status section).

## Fields and Options

### Purchase Order Fields

| Field | Description |
|-------|-------------|
| PO Number | System-generated identifier |
| Supplier | Supplier fulfilling this PO |
| Customer Order | Related customer order |
| Shipping Address | Delivery address |
| Shipping Method | From customer order |
| Due Date | Expected delivery date |
| Status | Current PO status |
| Total Price | Sum of items + shipping |
| Total Price Inc VAT | Total with VAT |

### Purchase Order Item Fields

| Field | Editable | Description |
|-------|----------|-------------|
| Supplier Product | No | Product being fulfilled |
| Quantity | Yes* | Units to fulfil |
| Price | No | Unit cost |
| Price Inc VAT | No | Unit cost with VAT |
| Weight | No | Unit weight |
| Status | Yes | Item status |
| Delivered At | No | Auto-set when delivered |

*Only editable when PO status is PENDING

## Status and Lifecycle

### PO Status Values

| Status | Level | Meaning |
|--------|-------|---------|
| **PENDING** | 1 | Created, awaiting processing |
| **PROCESSING** | 2 | Being prepared by supplier |
| **ACCEPTED** | 3 | Supplier confirmed |
| **REJECTED** | 4 | Supplier cannot fulfil |
| **REFUNDED** | 5 | Rejected items refunded |
| **SHIPPED** | 6 | Dispatched by supplier |
| **DELIVERED** | 7 | Received by customer |
| **CANCELLED** | 8 | PO cancelled |

### Status Transitions

| From | To (Allowed) |
|------|--------------|
| PENDING | PROCESSING, CANCELLED |
| PROCESSING | PENDING, ACCEPTED, REJECTED, CANCELLED |
| ACCEPTED | REJECTED, SHIPPED |
| REJECTED | REFUNDED |
| SHIPPED | DELIVERED |
| REFUNDED | (terminal) |
| DELIVERED | (terminal) |
| CANCELLED | (terminal) |

### Automatic Status Derivation

PO status is automatically derived from its items:
- Takes the lowest-level status among all items
- Example: If items are [PROCESSING, SHIPPED], PO is PROCESSING

### Typical Workflow

```
1. PENDING     - PO created from customer order allocation
2. PROCESSING  - Sent to supplier for review
3. ACCEPTED    - Supplier confirms they can fulfil
4. SHIPPED     - Supplier dispatches items
5. DELIVERED   - Customer receives items
```

### Rejection Flow

```
1. Item set to REJECTED (supplier can't fulfil)
2. PO status becomes REJECTED
3. Item set to REFUNDED (refund processed)
4. System re-allocates to alternative supplier
5. New PO created with different supplier
```

## Editing Rules

### When Edits Are Allowed

| PO Status | Quantity Editable | Status Editable |
|-----------|-------------------|-----------------|
| PENDING | Yes | Yes |
| PROCESSING | No | Yes |
| ACCEPTED | No | Yes |
| REJECTED | No | Yes (to REFUNDED) |
| SHIPPED | No | Yes (to DELIVERED) |
| REFUNDED | No | No |
| DELIVERED | No | No |
| CANCELLED | No | No |

### Quantity Constraints

When editing item quantity:
- Maximum = customer order item's outstanding qty + current PO item qty
- Setting to 0 removes the item
- Cannot exceed original order item quantity

## Pricing

### PO Pricing Calculation

| Calculation | Description |
|-------------|-------------|
| Item Total | Quantity × Unit Price |
| Items Subtotal | Sum of all item totals |
| Shipping | From customer order |
| Total | Items Subtotal + Shipping |
| VAT | Total Inc VAT - Total |

### Cost vs. Sell Price

- PO items use the supplier's **cost** price
- Customer order items use the **sell** price
- Profit = Sell Price - Cost Price

## Cascade Effects

When PO item status changes:

1. **PO status updates** - Derives from items
2. **Customer order item updates** - Derives from its PO items
3. **Customer order updates** - Derives from its items

Example chain:
```
PO Item → DELIVERED
    ↓
PO → DELIVERED (if all items delivered)
    ↓
Order Item → DELIVERED (if all PO items delivered)
    ↓
Customer Order → DELIVERED (if all items delivered)
```

## Warnings

- Quantity can only be edited when PO is PENDING
- Deleting all items deletes the entire PO
- Status changes are irreversible for terminal states
- DELIVERED and CANCELLED are final - no further changes allowed
- Rejections require manual refund processing
- Time constraints apply in automated workflows (shipping hours, etc.)
