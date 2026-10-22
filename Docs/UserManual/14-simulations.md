# Simulations

SupplyMars runs itself: simulated customers place orders and simulated suppliers accept, ship and deliver them, so there is always something to work on.

**Where:** it runs in the background. The one control in the app is **Create Demo Order** on **Orders > Orders**; see [Orders](08-orders.md#create-a-demo-order).

## What happens on its own

Times are UTC.

| What | When | What you see |
|---|---|---|
| New customer orders | About 12 an hour, at random moments | New Pending orders, often from customers you have not seen before |
| Orders allocated to suppliers | Every 15 minutes | Orders get purchase orders; an order moves to Processing once every line is allocated |
| Suppliers accept or reject POs | At :01, :16, :31 and :46 | Each run picks one supplier. About 1 PO in 50 is rejected, every item in it |
| Items shipped | On the hour, 09:00–17:00 | Each run picks one supplier at random. Its items accepted at least 2 hours ago are shipped, about 19 in 20 of them |
| Items delivered | On the hour, 07:00–20:00 | Each run picks one supplier at random. Its items shipped at least 12 hours ago are delivered, about 19 in 20 of them |
| Supplier stock and cost change | Every 15 minutes | One supplier's stock rises when low and falls otherwise; costs move up to 10% either way. Product prices do not follow these changes |
| Rejected POs refunded and re-allocated | 01:30, live site only | Each PO whose overall status is Rejected has its Rejected items set to Refunded, and the order is allocated again, possibly to the same supplier. A Rejected item on a PO whose overall status is not Rejected (another item is less far along, such as Accepted) is skipped |
| Reports refreshed | Every 30 minutes, finalised after midnight | See [Reports and Dashboard](13-reports.md) |

Things that follow from this:

- A locked order is skipped by allocation, acceptance, shipping and delivery until it is unlocked.
- Allocation takes the oldest unlocked Pending orders that have no purchase order yet. A line that no single supplier can supply in full is skipped. An order with no PO at all is tried again next run; an order where some lines got a PO and others did not is not picked up again, so allocate the rest by hand ([Order Items](09-order-items.md#allocate-an-item-to-a-supplier)).
- Because one supplier is picked per run, a given supplier's items can wait several hours to ship or be delivered.
- Nothing ships at night, so overnight orders stay Processing until the morning.
- On the public playground, all data is replaced each night at 02:15 with a copy of the live site, so anything you change there is gone the next day.

Operators who run these jobs by hand: commands, options and the full schedule are in the [CLI Reference](../09-cli-reference.md).

## Related

- [Orders](08-orders.md): demo orders, order statuses and the lock.
- [Purchase Orders](10-purchase-orders.md): PO statuses, rejection and refunds.
- [Stock](11-stock.md): how stock and the active source decide allocation.
