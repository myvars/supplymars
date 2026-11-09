---
title: Orders, purchase orders and a status tree
period: April – June 2024
summary: Customer orders and purchase orders arrived together, with prices copied onto each line and one editable purchase order per supplier per order. By June statuses were enums with transition rules, and an order's status stopped being set by hand; it is worked out from what its suppliers have done.
figures:
  - value: "4"
    label: entities, two orders and their lines
  - value: "5"
    label: statuses at each level
  - value: "1"
    label: editable purchase order per supplier per order
refs:
  - label: ADR 002 · Order line splitting
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/002-order-line-splitting.md
  - label: Sourcing and pricing
    url: https://github.com/myvars/supplymars/blob/main/Docs/04-sourcing-and-pricing.md
---

By the end of April 2024 Turtle had a catalogue: products, prices with markups and rounding, and suppliers with their own products, costs and stock. A shop also needs orders, and this one needs two kinds: the customer's order, and the purchase orders that ask suppliers to send the goods. On 30 April four entities arrived in one commit: the customer order and its lines, the purchase order and its lines.

## A line keeps its price

A customer order line is built from a product and a quantity, and copies what it needs at that moment: the product's sell price, with and without VAT, and its weight. The line's totals are worked out from its own copies with `bcmath`, never from the product. A product's price can change tomorrow, and an order placed today keeps the price it was placed at, unless someone edits the line.

## One purchase order per supplier

On 15 May purchase orders became real. From an order line, a person picks one of the product's supplier products, and the outstanding quantity goes on a purchase order to that supplier. Each order has at most one editable purchase order per supplier: a second line for the same supplier joins it rather than starting another. A purchase order copies the order's shipping address and reference, and each of its lines records the supplier product and copies its cost.

A purchase order line's quantity can be edited, but a validator caps it at its current quantity plus what the order line still needs, so a line is never ordered from suppliers in more units than the customer asked for. Reducing a purchase order line and putting the rest with another supplier splits an order line between suppliers, a choice a person makes.

## Statuses with rules

On 26 May statuses stopped being strings. `OrderStatus` and `PurchaseOrderStatus` became enums, each with five cases (pending, processing, shipped, delivered and cancelled), a default, and a `canTransitionTo()` that lists the legal moves:

```php
self::PROCESSING => match ($to) {
    self::SHIPPED, self::CANCELLED => true,
    default => false,
},
```

Shipping had been an enum since the orders arrived, with each method's price and due date. On 7 June the enums moved into a folder of their own, and on 12 June a purchase order line's status could be changed by hand, through a form whose validator checks the transition is allowed. The same day, a purchase order's status became the lowest of its lines'.

## The status tree

On 14 June, in the last commit for nearly six weeks, the tree went all the way up to the order, whose status is now worked out rather than set by hand. The order statuses got levels too, and the statuses roll up:

- A purchase order's status is the lowest of its lines'.
- An order line with nothing on a purchase order is pending, and one with some quantity on purchase orders and some still to source is processing. Otherwise its status is the lowest of its purchase order lines', mapped from the purchase order's status to the order's.
- The order's status is the lowest of its lines'.

So an order with one line delivered and one still shipping is shipping, and nobody has to decide that. Changing a purchase order line's status works its way up: the line updates its purchase order, then its order line, which updates the order.

The same commit added cancelling an order. Only a pending order can be cancelled, and cancelling it cancels each line, unless a line already has quantity on a purchase order. Then the cancel refuses, because that part is with a supplier. Cancelling goes down the tree, but not past a purchase order.

## Rules in the entities

In the middle of those weeks, on 22 May, a commit called "Code kaizen, move to rich entities" started moving questions into the entities themselves. `Product` gained methods such as `hasDefaultMarkup()`, `hasPriceModel()` and `hasActiveProductSource()`, and the services asked the product instead of checking its fields. By June the order entities held the status rules: `generateStatus()`, `cancelOrder()`, `allowCancel()`. That direction carried on, through [named constructors](/log/orders-built-whole) in 2025 to the [rebuild by bounded context](/log/rebuilding-by-bounded-context).

*Update, November 2026: the tree is still how status works. ADR 002 records it: status derives from the child purchase order items, the least progressed wins, and a line splits between suppliers only by hand, or after a cancel or refund. The statuses grew to eight levels for purchase orders when suppliers started [accepting, rejecting and refunding](/log/hardening-the-status-tree), and running the tree at simulation speed in September found the cases it got wrong.*
