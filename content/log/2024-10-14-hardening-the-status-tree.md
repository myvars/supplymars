---
title: Hardening the status tree
period: 20 September – 14 October 2024
summary: The simulator ran the status rules faster and more often than any person would, and the edge cases came out in a day. A refunded status, a command that re-derives every status, rules for cancelling a line, and rejected purchase orders refunded and sourced again.
figures:
  - value: "7"
    label: status commits on 20 September
  - value: "1"
    label: command to re-derive every status
  - value: 3½ weeks
    label: from the refunded status to re-sourcing
refs:
  - label: ADR 002 · Order line splitting
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/002-order-line-splitting.md
  - label: Sourcing and pricing
    url: https://github.com/myvars/supplymars/blob/main/Docs/04-sourcing-and-pricing.md
---

An order's status in Turtle is never set by hand. Each purchase order item has a status, and it rolls up: to its purchase order, to the order line it serves, and from the lines to the order. At each level the least-progressed child wins, so an order with one line delivered and one still shipping is shipping. Every status has a level, and every enum has a `canTransitionTo()` that says which moves are legal.

In the week of 14 September the [simulator](/log/six-days-to-a-supply-chain-that-runs-itself) started driving those rules: placing orders, sourcing them, and accepting, shipping and delivering on the suppliers' behalf. Run over and over, faster than any supplier would, it reached the edge cases quickly. Friday 20 September went on fixing them.

## A rejection that let go too early

When rejections arrived on 18 September, a rejected purchase order item stopped counting towards its line's quantity, the same as a cancelled one. The line went straight back to having quantity outstanding, with the rejected item still attached.

At 10:29 on Friday I added a status after rejected: refunded. A rejected item now holds its quantity on the line until it is refunded, and refunded is the only place a rejection can go. An hour later the line's roll-up learned to skip refunded items, and to fall back to pending when every item on a line has been refunded. That fallback couldn't be reached yet: an earlier check in the roll-up sent any line with quantity outstanding to processing first, and a fully refunded line always has some. It took until 29 September to fix.

## A command that re-derives everything

Fixing a rule doesn't fix the orders already saved under the old one. At 14:17 I added `app:refresh-order-status`. It walks the orders, oldest first, and re-derives every status from the bottom up: each purchase order from its items, each line from its purchase order items, the order from its lines. It prints every status it changes and dispatches the domain events, so each correction lands in the status log like any other change.

The next three commits, all in the 47 minutes after it, are each called "Fixed Order status bug". One gave the command an offset, to work through the orders in batches. One made the order's roll-up start from its first line rather than from cancelled. The third was the real find: a line on a cancelled order with nothing on a purchase order rolled up to pending, so re-deriving a cancelled order's statuses brought its lines back to life. Now a line on a cancelled order stays cancelled.

That fix had a cost of its own. The new check reads the line's order, and a line being created didn't have one yet, so placing an order failed. Three hours later the line was attached to its order before it was built, and orders flowed again.

The command stayed until the [rebuild by bounded context](/log/rebuilding-by-bounded-context).

## Cancelling a line

On 22 September a single line could be cancelled, not just a whole order. The rule is narrow: a line can be cancelled only while it is pending or processing and nothing has gone on a purchase order. A cancelled line drops out of the order's totals, and an order whose lines are all cancelled drops its shipping charge too.

A week later order processing got two fixes. A line with quantity outstanding but nothing on a purchase order, including a fully refunded one, had shown as processing; it now stays pending until something is ordered from a supplier. And sending an order's purchase orders to the suppliers now moves only the items still pending, so an item a supplier has already answered is left alone.

## All or nothing

Sourcing was strict from the start, on 16 September. A line goes to the cheapest active supplier with stock for the whole line; it is never split across two suppliers. If no supplier can cover it, the line waits. The order's purchase orders aren't sent to the suppliers until every line is covered, so nothing goes to a supplier while part of the order is still unsourced. From the order page, a person can also put a line on a supplier of their choosing.

## Refunded and sourced again

Apart from a refund command that lasted an hour on 20 September, nothing acted on the refunded status until 14 October, three and a half weeks later. `app:refund-purchase-orders` takes rejected purchase orders, refunds their items, and runs the sourcing again for the order. The refund frees the line's quantity, so the line goes to whichever supplier is cheapest with the stock now, which can be the supplier that rejected it. Unlike the other stages, it doesn't skip a locked order.

*Update, November 2026: ADR 002 records the allocation rules, which haven't changed in substance (a tie on cost now goes to the supplier with more stock): the whole outstanding quantity on one supplier, or the line is skipped. Until October 2026 the build command only picked up orders with no purchase order at all, so a line skipped for lack of stock waited for someone to click Allocate. It now retries orders that still have quantity outstanding, after the new ones.*
