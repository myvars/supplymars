# Order Items

An order item is one product line on a customer order: the product, the quantity, the price, and how much of it is on supplier purchase orders.

Press `?` on the order page for a short summary of orders.

**Where:** **Orders > Orders**, then open an order. Items are listed on the order page, under the order summary.

## The item card

Each item shows the product image and name, the price inc VAT, the quantity, the product number and a status badge.

- **Qty: 3** means nothing is on a purchase order yet. **Qty: 1 of 3** means 1 unit is still outstanding (not yet on a purchase order).
- The list shows only items with outstanding quantity. An item whose whole quantity is on purchase orders moves out of the list and appears in the **Purchase Orders** section below, inside its supplier's PO card. It has not disappeared.
- Click the status badge to see the item's status history.
- Click the product image to open the product.
- Click anywhere else on the card to show or hide the item's supplier list (see [Allocate an item to a supplier](#allocate-an-item-to-a-supplier)).

## Tasks

### Edit quantity or price

1. Click the pencil icon on the item card. It shows only while the item is Pending or Processing.
2. Change **Quantity** or **Price Inc VAT**.
3. Click **Update Order Item**.

The price ex VAT is worked out from the price inc VAT and the item's VAT rate. Purchase order prices do not change: they use the supplier's cost.

### Remove an item

Set **Quantity** to 0 and click **Update Order Item**. The item is deleted from the order. This works only while nothing is on a purchase order and the order can still be edited.

### Cancel an item

1. Click the pencil icon on the item card.
2. Click **Cancel Order Item** at the bottom of the modal. There is no confirm step.

The link shows only when nothing is on a purchase order and the item is Pending or Processing. A cancelled item stays in the list with a Cancelled badge, is left out of the order total, is never allocated, and cannot be reinstated.

### Allocate an item to a supplier

1. Click the item card to open its supplier list. It shows only while the item has outstanding quantity and can be edited.
2. Each row is an active supplier product for this product, with the supplier, product code, cost, and either "N in stock" or "Nd lead time". The list does not mark the cheapest source.
3. Click a row. **All** the outstanding quantity goes on that supplier's Pending purchase order for this order, or on a new one. There is no quantity field and no stock check.

The new purchase order item is Pending, and stays Pending until something moves it: suppliers never see a Pending item. When you have allocated every line, click **Allocate** on the order. With nothing left outstanding, it moves the order's Pending items to Processing. You can also set each item's **PO Item Status** to Processing on its purchase order ([Purchase Orders](10-purchase-orders.md#change-an-items-status)).

Lock the order while you allocate it by hand ([Orders](08-orders.md#lock-or-unlock-an-order)). On live and the playground, the scheduled allocation runs every 15 minutes. It picks up an unlocked order that has a purchase order and outstanding quantity, gives the rest to the best supplier, and then moves every Pending item on the order to Processing, including the ones you allocated, so they go to the suppliers.

**Allocate** on its own picks suppliers automatically; see [Orders](08-orders.md#allocate-an-order).

To split an item across suppliers, lower the quantity on the first purchase order item (pencil on its card in the order's Purchase Orders section, see [Purchase Orders](10-purchase-orders.md#change-an-items-quantity)), then allocate the rest from the supplier list. Lock the order first: once you have lowered the quantity, the scheduled allocation can give the rest back to the best supplier, which may be the same one, and undo the split.

## Rules and limits

| Rule | What you see |
|---|---|
| Quantity is 0 to 10,000 | "Please enter a valid quantity" |
| Quantity can't go below what is already on purchase orders, so an item with quantity on a purchase order can't be removed with 0 | "minimum quantity is N" |
| Price Inc VAT is 0 to 100,000 | "Please enter a product price inc VAT (0 to 100000)" |
| Only Pending and Processing items can be edited, cancelled or allocated | No pencil icon, no supplier list |
| Cancel and remove need nothing on purchase orders | No **Cancel Order Item** link |

- Outstanding quantity = item quantity minus the quantity on its purchase order items. Cancelled and refunded purchase order items do not count, so a refunded quantity becomes outstanding again. A cancelled item has no outstanding quantity.
- Example: quantity 10 with 5 on purchase orders can be edited to 5 or more. Quantity 10 with all 10 on purchase orders can't be lowered.

## Statuses

You never set an item's status. It follows its purchase order items, and the order's status then follows its items ([Orders](08-orders.md#statuses)). There is no transition check, so an item can move backwards, for example when its purchase order is rewound.

| Status | Badge | Meaning |
|---|---|---|
| Pending | Grey | No purchase order items yet, or all of them still Pending (or all refunded) |
| Processing | Yellow | Partly on purchase orders, or its purchase order items are Processing, Accepted, Rejected or Refunded |
| Shipped | Blue | Every purchase order item has shipped (refunded and cancelled ones aside) |
| Delivered | Green | Every purchase order item has been delivered (refunded and cancelled ones aside) |
| Cancelled | Red | Cancelled by staff, or every purchase order item was cancelled. Final |

When the whole quantity is on purchase orders, the item takes the least advanced status of its purchase order items, ignoring refunded ones. For example, purchase order items Accepted and Shipped give **Processing**, because Accepted counts as Processing for the item.

## Related

- [Orders](08-orders.md): orders, the Allocate button and order statuses
- [Purchase Orders](10-purchase-orders.md): purchase order items and their statuses
- [Stock](11-stock.md): how the automatic supplier choice works
