---
title: Hardening the status tree
period: 20 September – 14 October 2024
summary: The simulator ran the status rules faster and more often than any person would, and the edge cases came out in a day. A refunded status, a command that re-derives every status, rules for cancelling a line, and rejected purchase orders refunded and sourced again.
refs:
  - label: ADR 002 · Order line splitting
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/002-order-line-splitting.md
  - label: Sourcing and pricing
    url: https://github.com/myvars/supplymars/blob/main/Docs/04-sourcing-and-pricing.md
---

An order's status in Turtle is never set by hand: it [rolls up](/log/orders-purchase-orders-and-a-status-tree) from its purchase order items, and at each level the least-progressed child wins. In the week of 14 September the [simulator](/log/six-days-to-a-supply-chain-that-runs-itself) started driving those rules, faster and more often than any supplier would, and it reached the edge cases quickly. Friday 20 September went on fixing them.

## A rejection that let go too early

When rejections arrived on 18 September, a rejected purchase order item stopped counting towards its line's quantity, the same as a cancelled one. The line went straight back to having quantity outstanding, with the rejected item still attached.

First I added a status after rejected: refunded. A rejected item now holds its quantity on the line until it is refunded, and refunded is the only place a rejection can go. Then the line's roll-up learned to skip refunded items, and to fall back to pending when every item on a line has been refunded.

## A command that re-derives everything

Fixing a rule doesn't fix the orders already saved under the old one. So I added `app:refresh-order-status`. It walks the orders, oldest first, and re-derives every status from the bottom up: each purchase order from its items, each line from its purchase order items, the order from its lines. It prints every status it changes and dispatches the domain events, so each correction lands in the status log like any other change.

Three quick fixes followed. One gave the command an offset, to work through the orders in batches. One made the order's roll-up start from its first line rather than from cancelled. The third was the real find: a line on a cancelled order with nothing on a purchase order rolled up to pending, so re-deriving a cancelled order's statuses brought its lines back to life. Now a line on a cancelled order stays cancelled.

That fix had a cost of its own. The new check reads the line's order, and a line being created didn't have one yet, so placing an order failed. The fix was to attach the line to its order before building it, and orders flowed again.

The command stayed until the [rebuild by bounded context](/log/rebuilding-by-bounded-context).

## Cancelling a line

On 22 September a single line could be cancelled, not just a whole order. The rule is narrow: a line can be cancelled only while it is pending or processing and nothing has gone on a purchase order. A cancelled line drops out of the order's totals, and an order whose lines are all cancelled drops its shipping charge too.

A week later order processing got two fixes. A line with quantity outstanding but nothing on a purchase order, including a fully refunded one, had shown as processing; it now stays pending until something is ordered from a supplier. And sending an order's purchase orders to the suppliers now moves only the items still pending, so an item a supplier has already answered is left alone.

## Refunded and sourced again

Apart from a refund command that was added and removed on 20 September, nothing acted on the refunded status until 14 October, three and a half weeks later. `app:refund-purchase-orders` takes rejected purchase orders, refunds their items, and runs the sourcing again for the order. The refund frees the line's quantity, so the line goes to whichever supplier is cheapest with the stock now, which can be the supplier that rejected it.

*Update, November 2026: ADR 002 records the allocation rules, which haven't changed in substance: the whole outstanding quantity on one supplier, or the line is skipped. The build command now also retries orders that still have quantity outstanding, after the new ones, so a skipped line no longer waits for someone to click Allocate.*
