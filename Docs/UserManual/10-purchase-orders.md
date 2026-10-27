# Purchase Orders

A purchase order (PO) is what SupplyMars orders from one supplier to fulfil one customer order. An order allocated to three suppliers has three POs.

Press `?` on this screen for a short summary.

**Where:** **Purchasing > Purchase Orders**. **Reporting > Rejected POs** opens the same list filtered to Rejected.

## Tasks

### Find a purchase order

1. Go to **Purchasing > Purchase Orders**. Each row shows the PO number, order number, supplier, customer, date, total inc VAT and status.
2. Sort by **PO**, **Created**, **Order**, **Total** or **Status**.
3. To filter, click the filter icon in the search bar, fill in any of **Supplier**, **Purchase Order Id**, **Customer Order Id**, **Customer Id**, **with Product Id**, **Purchase Order Status**, **Start Date** and **End Date**, and click **Apply Filter**.

POs are created by allocation (see [Orders](08-orders.md#allocate-an-order) and [Order Items](09-order-items.md#allocate-an-item-to-a-supplier)), not from this list.

### Read a purchase order

Click a PO in the list, or a PO card on the order page. The PO page shows:

- The supplier, created date, a link to the customer order, and the PO status badge (click it for the status history).
- The delivery address and the **PO Summary**: item subtotal, shipping, total before VAT, VAT and grand total.
- The line and item counts with a profit badge, then one card per item: supplier product name, unit cost, quantity, profit badge, supplier product code, delivered date (when delivered) and status badge (click it for the item's status history).

### Change an item's status

1. On the **PO page**, click the pencil icon on the item card. It shows unless the item is Delivered or Cancelled.
2. Choose the new **PO Item Status**.
3. Click **Update Purchase Order Item**.

The list offers every status, but only the moves in the [transitions table](#statuses) are accepted. Any other choice gives "Status can not be set to X." (X is the status you chose, in capitals). Setting a Pending item to Processing here is how you send a purchase order to the supplier when the whole order was allocated by hand ([Order Items](09-order-items.md#allocate-an-item-to-a-supplier)).

### Change an item's quantity

Quantity is edited from the **order page**, not the PO page.

1. Open the customer order and scroll to **Purchase Orders**.
2. Click the pencil icon on the PO item card. It shows only while the item is Pending.
3. Enter the new quantity and click **Update Purchase Order Item**.

- The most you can enter is the item's current quantity plus the order item's outstanding quantity ("Quantity cannot be greater than N").
- 0 removes the item from the PO, and its quantity becomes outstanding again on the order item. Removing the last item deletes the PO.

### Rewind a purchase order to Pending

1. On the PO page, click the counter-clockwise arrow icon at the top right of the PO card.
2. Click **Rewind to Pending**.

Every item goes back to Pending, delivered dates are cleared, and the status history of the PO and its items is deleted. The order item and order statuses are worked out again, which adds new entries to the order's history. The arrow is always offered, even on a Pending PO. A PO with a Refunded or Cancelled item is refused with "Cannot rewind: the purchase order has refunded or cancelled items.": that item's quantity has been released, and usually allocated again, so resetting it would put the order item on purchase orders twice. It cannot be undone.

The rewound items stay Pending until you click **Allocate** on the order (or set them to Processing here).

## Rules and limits

- Status changes are made per item. The PO status is never set directly: it is the least advanced status of its items (Pending first, Cancelled last). Items Processing and Shipped give a Processing PO.
- Each item change also updates the order item and the order ([Order Items](09-order-items.md#statuses)).
- PO items use the supplier's **cost**. The customer pays the **sell** price. The profit badges show the difference.
- A Refunded item still shows the pencil icon and the list of every status, but each one is refused with "Status can not be set to X."
- No PO action changes supplier stock ([Stock](11-stock.md)).
- Old status history is pruned on live ([Navigation](02-navigation.md#detail-pages)).

### When a supplier rejects

1. The item is set to Rejected (by staff or by the supplier simulation).
2. On live, a nightly job at 01:30 takes each purchase order whose overall status is Rejected, sets its Rejected items to Refunded and allocates the order again. A Rejected item on a PO whose overall status is not Rejected (another item is less far along, such as Accepted) is skipped. Don't refund it by hand while the PO's other items are still in progress: the PO's status would then have to move from Accepted to Refunded when they ship, which is not allowed, so they can never be shipped, and a PO with a Refunded item can't be rewound. Refund it only once the other items are Delivered. The playground does not run the job.
3. The new allocation picks the best source again, so it can be the same supplier.

You can do the same by hand: set the item to Refunded, then click **Allocate** on the order. If you don't click Allocate, the scheduled allocation (every 15 minutes on live and the playground) reallocates the line itself, unless the order is locked.

## Statuses

| Status | Badge | Meaning |
|---|---|---|
| Pending | Grey | Created by allocation, not yet sent to the supplier |
| Processing | Yellow | Sent to the supplier, waiting for an answer |
| Accepted | Emerald | The supplier will supply it |
| Rejected | Orange | The supplier can't supply it |
| Refunded | Purple | Rejected item refunded. Its quantity is outstanding again on the order item |
| Shipped | Blue | The supplier has dispatched it |
| Delivered | Green | Delivered to the customer. Final |
| Cancelled | Red | Cancelled. Final |

| From | Can move to |
|---|---|
| Pending | Processing, Cancelled |
| Processing | Pending, Accepted, Rejected, Cancelled |
| Accepted | Rejected, Shipped |
| Rejected | Refunded |
| Shipped | Delivered |
| Refunded, Delivered, Cancelled | nothing (final) |

The usual path is Pending → Processing → Accepted → Shipped → Delivered. Pending items move to Processing when an **Allocate** run leaves nothing outstanding on the order, or when you set them to Processing here. How the simulation moves POs on its own is in [Simulations](14-simulations.md).

## Related

- [Orders](08-orders.md): orders and the Allocate button
- [Order Items](09-order-items.md): order items, manual allocation and item statuses
- [Suppliers](05-suppliers.md)
- [Sourcing and Pricing](../04-sourcing-and-pricing.md) (technical): the full allocation and status rules
