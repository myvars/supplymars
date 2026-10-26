# Orders

A customer order holds the items a customer bought, the shipping method and the delivery address. Its items are fulfilled through purchase orders to suppliers.

Press `?` on this screen for a short summary.

**Where:** **Orders > Orders** (all orders) or **Orders > Pending Orders** (the same list filtered to Pending)

## Tasks

### Find an order

1. Open **Orders > Orders**. Each card shows the order number, customer, created date, your reference, total inc VAT and status.
2. Sort by **Order**, **Created**, **Customer**, **Total** or **Status**.
3. To filter, click the funnel icon, set **Customer Order Id**, **Purchase Order Id**, **Customer Id**, **with Product Id**, **Order Status**, **Start Date** or **End Date**, and click **Apply Filter**.

### Create an order (super admin only)

1. Click **Create Order**. Other staff don't see this button.
2. Fill in **Customer Id** (the customer's number), **Shipping Method** (Three Day Shipping or Next Day Shipping) and **Customer order ref** (optional, up to 255 characters).
3. Click **Create Order**.

The order starts as Pending with no items. It takes the customer's default shipping and billing addresses, and its due date from the shipping method.

### Create a demo order

1. Click **Create Demo Order** (lightning icon) on the order list.
2. The dialog shows how many of today's 10 demo orders have been made ("3 of 10 demo orders created today."). Click **Create Order**.

A toast confirms "Demo order #000123 created." The demo order has 1 to 5 lines of random products (active, in stock and with an active supplier product), 1 to 5 of each, and a reference starting `DEMO-`. It goes to an existing customer about 1 time in 3, otherwise to a newly made random customer. You return to the list, where the new order is briefly highlighted. Demo orders are real orders: they go through allocation, suppliers and reports like any other.

### Read an order

Click an order. The page has:

- **Order card:** order number, lock icon, created date, reference, **Allocate** (while the order can be edited), status (click it for the status history) and, while the order is Pending, an X icon to cancel.
- **Delivery Address** and **Order Summary:** Item(s) subtotal, Shipping, VAT, Total.
- **Items:** "N lines (M items)", **Add Item**, and one card per item that still has quantity not yet on a purchase order. Editing, removing, cancelling and allocating an item by hand are in [Order Items](09-order-items.md).
- **Purchase Orders:** the purchase orders made for this order.

The page updates by itself while you have it open: when someone else, or a scheduled job, changes an item, a purchase order, a status, the totals or the lock, you see the change within a few seconds without reloading. An open dialog or menu stays open while it does.

### Add an item

1. Click **Add Item**.
2. Enter **Product Id** (the product's number) and **Quantity** (1–10,000).
3. Click **Create Order Item**.

The item's price is the product's current sell price. To change, remove or cancel it afterwards, see [Order Items](09-order-items.md#tasks).

### Allocate an order

Click **Allocate**. The app gives each line with outstanding quantity to one supplier that can supply all of it, and creates or adds to that supplier's purchase order. When nothing is left outstanding, it also moves every Pending purchase order item on the order to Processing. How the supplier is chosen: [Stock](11-stock.md#how-the-active-source-is-chosen).

If you allocated every line by hand from the supplier lists, Allocate has nothing to do ("No items to allocate.") and the purchase order items stay Pending; see [Order Items](09-order-items.md#allocate-an-item-to-a-supplier) for the way out.

### Cancel an order

1. Click the X icon on the order card.
2. Click **Cancel Order** (or **Go Back**).

Every item is cancelled and the order becomes Cancelled.

### Lock or unlock an order

Click the lock icon on the order card. While locked, the card shows "Locked by {name}". Click it again to unlock.

If you unlock an order someone else locked, they see a message on whatever page they have open, "{your name} released your lock on order #000123", with a **View** link to the order. Nothing is sent when you lock an order or unlock your own. On the playground everyone shares one login, so this message never appears there.

## Rules and limits

| Action | Blocked when | Message |
|---|---|---|
| Create Order | You are not a super admin | Button not shown |
| Create a demo order | 10 demo orders already made today (by anyone) | The dialog says "You have reached the daily limit of 10 demo orders." and offers only **Close** |
| Allocate | No line has outstanding quantity (including when every line was allocated by hand) | "No items to allocate." |
| Allocate | A line has no single supplier with enough stock | "Cannot allocate all items" (the other lines are still allocated) |
| Create Order | The customer has no default shipping or billing address (newly registered customers have none) | "Customer has no default shipping or billing address." |
| Cancel order | The order is not Pending, or an item has quantity on a purchase order | "Order cannot be cancelled" / "Items cannot be cancelled" |

Item rules and messages (quantity, price, cancel) are in [Order Items](09-order-items.md#rules-and-limits).

- **The demo limit is shared:** 10 per day for everyone together, not per user. The count starts again at midnight.
- **Only Pending orders can be cancelled.** To stop work on a Processing order, change its purchase order items; there is no action that cancels a whole purchase order. See [Purchase Orders](10-purchase-orders.md).
- **The lock is a note for people, but the scheduled jobs respect it.** Nothing stops anyone editing a locked order, and anyone can unlock it. The scheduled allocation, supplier acceptance, shipping and delivery jobs skip a locked order until it is unlocked.
- **Items and Allocate** are available while the order is Pending or Processing.
- Nothing checks that a product is active, complete or in stock when you add it.

## Shipping methods

| Method | Price (exc VAT) | Due |
|---|---|---|
| Three Day Shipping | £3.99 | 3 days after the order is created |
| Next Day Shipping | £9.99 | 1 day after the order is created |

VAT on shipping uses the default VAT rate. Totals leave out cancelled items, and a cancelled order has no shipping charge.

## Statuses

The order's status is the least advanced status among its items, in this order: Pending, Processing, Shipped, Delivered, Cancelled. An order with one Pending item stays Pending; cancelled items don't hold the order back. Item statuses: [Order Items](09-order-items.md#statuses).

| Status | Badge | Meaning |
|---|---|---|
| Pending | Grey | At least one item is Pending: not yet on a purchase order, or its purchase order is still Pending |
| Processing | Yellow | No item is Pending, and at least one has not shipped |
| Shipped | Blue | Every item has shipped or got further |
| Delivered | Green | Every item is delivered (or cancelled) |
| Cancelled | Red | Every item is cancelled |

Apart from Cancelled, no order status is final. Whenever an item changes, the order's status is worked out again from its items, with no check on where it came from. So it can move backwards: rewinding a purchase order to Pending returns even a Delivered order to Pending.

## Related

- [Order Items](09-order-items.md)
- [Purchase Orders](10-purchase-orders.md)
- [Customers](07-customers.md)
- [Stock](11-stock.md)
- [Simulations](14-simulations.md): what happens to orders on their own
