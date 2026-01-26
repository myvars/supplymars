# Orders

## What Orders Are For

Orders (Customer Orders) represent purchases made by customers. Each order contains:

- Customer information
- Shipping and billing addresses
- One or more order items (products)
- Shipping method and cost
- Total pricing including VAT

Orders progress through a lifecycle from creation to delivery, with items being allocated to suppliers via purchase orders.

## What You Can Do

- View all orders with search and filtering
- Create new orders
- Add items to orders
- Edit order item quantities and prices
- Cancel orders (when pending)
- Allocate orders to suppliers
- Lock/unlock orders to prevent concurrent editing
- View order status history

## Screens and Actions

### Order List

**Navigate to:** Orders

The order list shows all orders with:
- Order number (#000001 format)
- Customer name
- Created date
- Total price (including VAT)
- Current status

**Sorting options:** ID, Created Date, Customer Name, Total Price, Status

**Filtering options:**
- By Order ID
- By Purchase Order ID
- By Customer ID
- By Product ID (orders containing a product)
- By Order Status
- By Date Range (start and end dates)

### Create an Order

**Navigate to:** Orders > Click **Create Order**

1. Fill in the form:
   - **Customer ID** (required) - Enter customer's ID number
   - **Shipping Method** (required) - Three Day or Next Day
   - **Customer Order Reference** (optional) - Custom reference

2. Click **Save**

The order is created with:
- Shipping address from customer's default
- Billing address from customer's default
- Due date calculated from shipping method
- Status set to PENDING

### View Order Details

**Navigate to:** Click on any order

The detail page shows:

**Order Card:**
- Order number and created date
- Customer order reference (if set)
- Lock status
- Allocate button (if editable)
- Status (clickable to view history)

**Summary Card:**
- Delivery address
- Items subtotal
- Shipping cost
- Total before VAT
- VAT amount
- Grand total

**Items Card:**
- Line and item counts
- Add Item button (if editable)
- List of all order items with:
  - Product image and name
  - Unit price
  - Quantity (outstanding/total)
  - Status
  - Supplier allocation options

**Purchase Orders Section:**
- Related purchase orders for this order
- Supplier fulfillment status

### Add Items to an Order

**Navigate to:** Order detail > Click **Add Item**

1. Fill in the form:
   - **Product ID** (required) - Product to add
   - **Quantity** (required) - Number of units (1-10,000)

2. Click **Save**

The item is added with the product's current sell price.

### Edit an Order Item

**Navigate to:** Order detail > Click item's **Edit** button

You can update:
- **Quantity** - Must not be less than quantity already allocated
- **Price Inc VAT** - Override the unit price

### Cancel an Order

**Navigate to:** Order detail > Click **Cancel**

1. Confirm cancellation
2. All items are cancelled
3. Order status changes to CANCELLED

**Requirements:** Order must be in PENDING status with no items allocated to purchase orders.

### Cancel an Order Item

**Navigate to:** Order detail > Click item's **Cancel** button

**Requirements:**
- Item must have no quantity allocated to purchase orders
- Item status must be PENDING or PROCESSING

### Allocate an Order

**Navigate to:** Order detail > Click **Allocate**

This automatically:
1. Finds available supplier products for each item
2. Creates purchase orders for each supplier
3. Links order items to purchase order items
4. Updates order status to PROCESSING

### Lock/Unlock an Order

**Navigate to:** Order detail > Click **Lock** or **Unlock**

- **Locked:** Shows which user locked it; prevents others from editing
- **Unlocked:** Anyone can edit

Use locking when working on an order to prevent conflicts.

## Fields and Options

### Order Fields

| Field | Required | Description |
|-------|----------|-------------|
| Customer | Yes | Customer placing the order |
| Shipping Method | Yes | THREE_DAY or NEXT_DAY |
| Customer Order Reference | No | Custom reference (max 255 chars) |
| Shipping Address | Auto | From customer's default |
| Billing Address | Auto | From customer's default |
| Due Date | Auto | Calculated from shipping method |

### Order Item Fields

| Field | Required | Description |
|-------|----------|-------------|
| Product | Yes | Product being ordered |
| Quantity | Yes | Units to order (1-10,000) |
| Price | Auto | Unit price from product |
| Price Inc VAT | Auto | Unit price with VAT |
| Weight | Auto | Unit weight from product |

### Shipping Methods

| Method | Shipping Cost | Due Date |
|--------|---------------|----------|
| Three Day | £3.99 | 3 days from order |
| Next Day | £9.99 | 1 day from order |

## Status and Lifecycle

### Order Status

| Status | Level | Meaning |
|--------|-------|---------|
| **PENDING** | 1 | Created, not yet allocated |
| **PROCESSING** | 2 | Allocated to suppliers |
| **SHIPPED** | 3 | Items dispatched |
| **DELIVERED** | 4 | All items delivered |
| **CANCELLED** | 5 | Order cancelled |

### Status Transitions

```
PENDING → PROCESSING (when allocated)
PENDING → CANCELLED (manual cancellation)
PROCESSING → SHIPPED (when items ship)
SHIPPED → DELIVERED (when items delivered)
```

### Automatic Status Updates

Order status is automatically derived from item statuses:
- The status with the lowest level among items becomes the order status
- Example: If items are [PROCESSING, SHIPPED], order is PROCESSING

### Item Status

Order items have the same status values as orders. Item status is derived from related purchase order item statuses.

## Pricing Calculations

| Calculation | Formula |
|-------------|---------|
| Item Total | Unit Price × Quantity |
| Item Total Inc VAT | Unit Price Inc VAT × Quantity |
| Subtotal | Sum of all item totals |
| Shipping Inc VAT | Shipping × (1 + VAT Rate) |
| Order Total | Subtotal + Shipping |
| Order Total Inc VAT | Subtotal Inc VAT + Shipping Inc VAT |

Cancelled items are excluded from totals.

## Warnings

- Orders can only be cancelled when in PENDING status
- Once allocated, orders cannot be cancelled (cancel individual POs instead)
- Editing item quantity cannot reduce below allocated amount
- Deleting items is not possible; cancel them instead
- Locked orders can only be modified by the user who locked them
- Order totals automatically recalculate when items change
