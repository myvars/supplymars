---
title: Six days to a supply chain that runs itself
period: September 2024
summary: One lifecycle stage a day, from placing an order to delivering it, each a console command with rules meant to look like a real supplier's. The status log became the simulator's clock, a lock let a person take an order out of the machine's hands, and supplier stock started to move on its own.
figures:
  - value: "6"
    label: days from placing orders to delivering them
  - value: "5"
    label: commands, one per lifecycle stage
  - value: 1 in 50
    label: purchase orders rejected, down from 1 in 25
  - value: "4"
    label: stages that skip a locked order
refs:
  - label: ADR 003 · Simulation-first design
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/003-simulation-first-design.md
  - label: CLI reference
    url: https://github.com/myvars/supplymars/blob/main/Docs/09-cli-reference.md
---

By September 2024 Turtle had a catalogue, prices, customer orders and purchase orders, with statuses that roll up from what each supplier has done. All of it moved only when someone clicked. An operations tool with nobody operating it shows empty screens, so I set out to make the business run itself: one stage of an order's life at a time, each as a console command, until an order could go from placed to delivered without anyone touching it.

## Day one: orders

On Saturday 14 September, `app:create-customer-orders` started placing orders. It takes a count, and with `--random` places up to that many. Each order has one to five lines of one to five units, picked from the catalogue, with a random shipping method. One order in three comes from an existing customer and the rest from a new one, and each order gets a new default address for its customer. Two days later each order waited a random pause before the next, up to 300 seconds divided by the count, so a batch arrived over a few minutes rather than in the same second. That pause stayed until October 2026.

The day before, two setup commands had filled in the supply side: `app:create-warehouse-products` and `app:create-supplier-products`, which matches each supplier's products to the catalogue by manufacturer part number and creates a product where there's no match.

The first version built customers and picked products with the Foundry test factories, which was quick and wrong: test factories in a production command. Products moved to a repository query on 20 September, and customers took until [June 2025](/log/leaving-the-bitnami-box).

## Day three: purchase orders

On Monday `app:process-customer-order`, soon renamed `app:build-purchase-orders`, took the oldest pending orders that had no purchase orders yet and sourced each line: the cheapest active supplier with enough stock for the whole line got it. That evening the logic moved out of the command into a service and gained a rule: only when every line is covered do the purchase order items move from pending to processing, the point where a supplier would see them. The next morning a link on the order page ran the same service, so the command and a person sourcing by hand went through one path.

## Day five: suppliers answer

On Wednesday purchase orders gained two statuses, accepted and rejected, and `app:process-purchase-order`, renamed `app:accept-purchase-orders` the next day, started answering for the suppliers: pick a supplier, take its waiting purchase orders, and accept each one, or reject one in 25. A week later it went to one in 50.

## A lock for people

The commit that added those two statuses, just after noon, also added the order lock. A signed-in user can lock an order, and the order card shows a padlock with their name. The lock doesn't stop other users; it stops the machine. Every query the simulation uses to pick work adds one condition:

```php
->andWhere('co.orderLock IS NULL')
```

So in September no command sourced, accepted, shipped or delivered a locked order, and whoever holds the lock can work on it knowing the simulation won't move it. Anyone can release any lock. Two years later the build, accept, ship and deliver commands still skip a locked order with that line, and since [October 2026](/log/live-pages-with-mercure) the holder gets a toast when someone else releases their lock, since the simulation may then pick the order up. The one gap is refunds: `app:refund-purchase-orders`, added in October, doesn't check the lock, and still doesn't.

## Day six: ship and deliver

On Thursday the stages split into one command each, and the last two arrived. Each has rules meant to look like a real supplier's:

| Command | When it runs | Waits since the last stage | Chance per run |
|---------|--------------|----------------------------|----------------|
| `app:ship-purchase-order-items` | 9:00 to 18:00 | 2 hours since accepted | 20 in 21 |
| `app:deliver-purchase-order-items` | 7:00 to 21:00 | 12 hours since shipped | 20 in 21 |

The waits needed a clock, and the status log, the record of every status change written since July, already was one. To decide whether an item can ship, the command looks up when it was accepted. The waits don't read timestamp columns; the audit trail answers the question. Each command signs in as a default staff account first, so every change it makes is logged against a user, like a change made in the browser.

With five commands, an order could now go from placed to delivered in the time those rules allow, with nobody clicking. Nothing in the repository ran them on a timer yet. That came with the [cron container](/log/summer-2025-the-production-stack) in June 2025.

## Running at full speed

Running the commands over and over, faster than any supplier would, put the status rules under real load. The next morning I added refunds, and by the end of the day a command that re-derives every status. That story is in [Hardening the status tree](/log/hardening-the-status-tree).

Two more things came out of the same fortnight. On 23 September every order, line, purchase order and item got a status timeline drawn from the log, showing how long after the record was created each status came. And on 21 September the admin screens got their first access check. Until then they needed no login at all; the commit says it was to stop crawlers.

## Stock that moves

A supplier whose stock never changes isn't much of a test of sourcing. On 26 September `app:update-supplier-stock` started moving it: pick a supplier and some of its products, and drain each product's stock by up to 10%. When stock falls to 20 or below, it restocks by up to 100, and the cost moves up or down by up to 10%. `app:reset-supplier-stock` sets each of a supplier's products to a stock level between 0 and 300. Three days later every stock and cost change went into a log of its own, through domain events.

*Update, November 2026: the shipping and delivery rules are now one service, `ProcessingSimulator`, with the same hours and waits they had in 2024: 9 to 18 and 2 hours, 7 to 21 and 12 hours. The chance per run went from 20 in 21 to 19 in 20, an even 95%. Rejections are still one in 50. ADR 003 records the simulation-first design.*
