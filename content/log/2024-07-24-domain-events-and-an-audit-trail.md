---
title: Domain events and an audit trail
period: July 2024
summary: Orders, lines, purchase orders and their items started raising events, and one status log recorded every change with who made it and when. The services dispatched the events after the flush, by hand, and Doctrine's own listeners were kept apart.
refs:
  - label: Architecture · Domain events
    url: https://github.com/myvars/supplymars/blob/main/Docs/03-architecture.md#domain-events
---

By June 2024 an order's status in Turtle was worked out from its suppliers' purchase orders, not set by hand. That also meant statuses changed without anyone choosing a value: one purchase order item moving could change its purchase order, its order line and the order. After nearly six weeks without a commit, I came back in late July to record those changes.

## Entities raise, services dispatch

The four entities at the centre, the customer order, its lines, the purchase order and its items, now raise events. A small trait gives each one a list of the events it has raised. The constructor raises a created event, and changing the status raises a status-changed event. Raising an event only records it; nothing runs yet.

The services that change orders release the events after they flush. `EditPurchaseOrderItem`, for example, saves the item and then hands a `DomainEventDispatcher` every entity the change could have touched: the item, its purchase order, the order line and the order. The dispatcher takes each entity's events, stamps them with the signed-in user, and sends them through Symfony's event dispatcher. Dispatching after the flush means the change is saved before anything reacts to it, and every new record has its database ID by the time a listener reads it. The eight order and purchase order services that had a `$flush` flag, to leave the save to their caller, lost it. Each operation now saves, then announces.

## One log for four kinds of record

Four listeners, one per status-changed event, write to a single table, `status_change_log`. A row holds the event type, the ID of the record that changed, its status, the user and the time. The event carries the entity itself, and the listener reads the status off it when it runs. The type and ID together say what the row is about, so orders, lines, purchase orders and their items share one table, and the history of any of them could be read from it.

The user is required: a change with no one behind it can't be logged. When the simulator arrived two months later, its purchase order commands signed in as a default staff account before changing anything, so their changes are recorded like anyone else's. The same log became the simulator's clock: it decides an item can ship by looking up [when it was accepted](/log/six-days-to-a-supply-chain-that-runs-itself).

## Two kinds of event

Turtle already had eight Doctrine listeners, doing jobs such as recalculating prices when a category, subcategory or VAT rate changed, updating stock and order totals, and removing a product's image files. They react to persistence: an entity was updated, so update what depends on it. Domain events say something happened in the business: a purchase order item was accepted. The same commit moved the Doctrine listeners into a `DoctrineEvents` folder of their own, so the two kinds sit apart.

*Update, November 2026: in October 2025 [one dispatcher after every flush](/log/domain-events-take-two) replaced the hand-written dispatch, events became small facts carrying IDs rather than entities, and the Doctrine listeners gave way to domain events. The status log grew past five million rows, and a nightly command now prunes old rows for records that have reached a final status.*
