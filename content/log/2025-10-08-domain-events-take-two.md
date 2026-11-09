---
title: Domain events, take two
period: October 2025
summary: Events that carried whole entities became small, past-tense facts with an ID and what changed. One listener dispatches them after each flush, replacing calls by hand in sixteen classes, and the Doctrine hooks that held the pricing rules gave way to events.
figures:
  - value: 16 → 1
    label: places that dispatch events
  - value: "8"
    label: Doctrine entity listeners retired
  - value: "17"
    label: domain events by January
refs:
  - label: Architecture · Domain events
    url: https://github.com/myvars/supplymars/blob/main/Docs/03-architecture.md#domain-events
  - label: Sourcing and pricing
    url: https://github.com/myvars/supplymars/blob/main/Docs/04-sourcing-and-pricing.md
---

SupplyMars has raised domain events since 2024. An order records that its status changed, and a listener writes the audit log. In October 2025, before rebuilding the code by bounded context, I rewrote how events are shaped and dispatched.

## What an event carries

The first events carried the entity. `OrderStatusChangedEvent` held the whole `CustomerOrder`, and its listener read the status off the order when it ran. So it logged whatever the status was by then, not the change the event was raised for. The base class also had a setter for the user, so an event could be changed after it was raised.

The new events are facts, named in the past tense, that can't be changed:

```php
class OrderStatusWasChangedEvent extends AbstractDomainEvent implements StatusWasChangedEventInterface
{
    public function __construct(
        private readonly CustomerOrderPublicId $publicId,
        private readonly StatusChange $statusChange,
    ) {
        parent::__construct(DomainEventType::ORDER_STATUS_CHANGED);
    }
}
```

An event carries the order's public ID and a `StatusChange` value object, before and after. The entity raises it only when the status actually changes, inside the method that changes it. Every event has a type and the time it occurred. A listener that needs the order loads it by ID; the event never hands out a live entity.

One listener now logs all four status changes, for orders, order items, purchase orders and their items, through the interface they share, in place of a listener class for each.

## Dispatched once, after the flush

Entities recorded events, but something had to send them, and that was each service's job. Sixteen classes, among them the order, purchase order and supplier product services, the generic CRUD actions and a stock command, called `dispatchProviderEvents()` by hand after their flush, passing the entities they expected to have changed. Any service that left an entity out would lose its events, with nothing to say so.

Now one Doctrine listener does it. On `postFlush` it goes through every entity the entity manager holds, releases the events each one recorded, and dispatches them. Events that implement `AsyncDomainEventInterface` also go to the Messenger queue. Services just flush. An event is dispatched only once its change is in the database, and every flush, from any code path, dispatches the events its changes raised.

## Rules out of the persistence layer

Eight Doctrine entity listeners held business rules: six for pricing and sourcing, one for order totals and one for product images. `CategoryPriceUpdater`, for example, checked in `preUpdate` whether `defaultMarkup`, `priceModel` or `vatRate` had changed, stored the affected products in a property, and repriced them in `postUpdate`. The business rule, that a category's markup change reprices the products that inherit it, was written as a database hook keyed on field names.

On the rebuild branch, all eight went. The entity now says what changed, in the method that changes it:

```php
$this->raiseDomainEvent(new CategoryPricingWasChangedEvent(
    $this->getPublicId(),
    $vatRateChanged,
    $markupChanged,
    $priceModelChanged,
));
```

A listener in the Pricing context reprices the products. Catalog says what happened, and Pricing decides what that means for prices, without either reaching into the other's internals. The same went for VAT rates, subcategories, suppliers and supplier products. Order totals, which a listener used to recalculate when an item's quantity or price changed, moved into the order's own methods. By the merge in January there were 17 domain events across the contexts.

*Update, October 2026: the dispatcher had two bugs, both found and fixed in October 2026. It looked for events in the entity manager's identity map, which a flush has already emptied of deleted entities, so their events were dropped, and the product image delete handler had been dispatching its event by hand to get round it. It now notes deletions in `onFlush`, and the handler just deletes. And a listener that flushed ran the dispatcher again, which dispatched the next entity's events from inside the first; a flush of 50 supplier products nested deep enough to hit Xdebug's 512-frame limit. It now releases every event before dispatching any. No event is async yet. The interface is ready for the first job that can wait.*
