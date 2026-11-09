---
title: A domain model that owns its rules
period: 2024 – 2026
summary: The thread that runs through every rewrite. Entities that answer for themselves, built whole, with typed IDs and immutable values, one path for every write, and rules checked where the change is made.
refs:
  - label: Architecture
    url: https://github.com/myvars/supplymars/blob/main/Docs/03-architecture.md
  - label: ADR 014 · Architecture rules
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/014-architecture-rules.md
  - label: "#115 · Rules in handlers, not only in forms"
    url: https://github.com/myvars/supplymars/pull/115
---

From the first commit, the aim for SupplyMars was a domain model that holds the business's rules: an order knows when it can be cancelled, a product knows where its markup comes from, and the code around them asks rather than decides. It didn't arrive in one go. Each step came when the code showed where the next one was needed, and most of them have a post of their own. This one follows the thread.

## Entities that answer for themselves

In May 2024 a commit called "Code kaizen, move to rich entities" started moving questions into the entities. A product gained methods such as `hasDefaultMarkup()` and `hasActiveProductSource()`, and the services asked the product instead of checking its fields. Statuses became enums that know which moves are legal, and by June the order entities held their own rules: [the status tree](/log/orders-purchase-orders-and-a-status-tree) works out an order's status from its lines, and an order decides whether it can be cancelled.

In July the same entities started [raising domain events](/log/domain-events-and-an-audit-trail) when they changed, and one status log recorded every change, with who made it and when.

## Built whole

In February 2025 the four entities at the heart of the business, orders, order lines, purchase orders and their items, got [private constructors and named ones](/log/orders-built-whole), such as `CustomerOrder::createFromCustomer()`. An order can only be created with what it needs to be valid, its setters went private, and its getters stopped returning nulls that couldn't happen. The test factories build through the same constructors, so a test can't create an order the application never could.

## Small values that can't change

In October 2025 identity and change became values of their own. Every entity behind a URL got [a typed ID](/log/a-public-ulid-for-every-entity), set in its constructor, so an order's ID can't be passed where a purchase order's is expected. Events became [small, immutable facts](/log/domain-events-take-two) carrying IDs rather than live entities, and a status change became a value object:

```php
final readonly class StatusChange
{
    private function __construct(
        private \BackedEnum $before,
        private \BackedEnum $after,
    ) {
    }

    public static function from(\BackedEnum $before, \BackedEnum $after): self
    {
        return new self($before, $after);
    }

    public function hasChanged(): bool
    {
        return $this->before !== $this->after;
    }
}
```

The business rules that had lived in Doctrine listeners, keyed on field names, moved into the entities' own methods and into events that other contexts react to.

## One path for every write

The [rebuild by bounded context](/log/rebuilding-by-bounded-context), merged in January 2026, gave every write the same shape. A form fills a form model, never an entity. A mapper turns it into a readonly command, a handler loads the entity and calls one of its methods, and the result is ok or failed with a message. The command says only what is being asked, such as `CancelOrder` with the order's ID. The entity's method does the rest. Locking an order shows the shape most of them now have: check, change, and say what happened.

```php
public function lockOrder(?User $holder, User $changedBy): void
{
    $previous = $this->orderLock;
    if ($previous === $holder) {
        return;
    }

    $this->orderLock = $holder;

    $this->raiseDomainEvent(new OrderLockWasChangedEvent(
        $this->getPublicId(),
        $previous?->getPublicId(),
        $holder?->getPublicId(),
        $changedBy->getPublicId(),
    ));
}
```

The same handlers serve [the REST API](/log/a-rest-api-next-to-a-server-rendered-app), which adds no business logic of its own.

## Rules where the change is made

By October 2026 the remaining work was making sure nothing could go around the model:

- **Between contexts.** The rule that a context may use another's domain but never its internals had been an aim since the rebuild. Deptrac now checks it on every change, and the build fails on a new breach (ADR 014).
- **Not only in a form.** Six rules lived only in a form or a template, so the inline edit, the API or a hand-made request could skip them. Each moved to where the change is made ([#115](https://github.com/myvars/supplymars/pull/115)). Whether a customer may review a product is now `ProductReview::ineligibility()`, asked by the handler and by the form alike, and editing a staff account goes through the project's first security voter.
- **Tests that respect the model.** The domain tests stopped writing private properties through reflection. They add items through the aggregates' own methods and reach a status the way the domain does, through `generateStatus()`.

## What it pays for

A model that owns its rules is easier to trust and easier to build on. A new caller, an API endpoint, a console command or a live page, uses the same methods and gets the same answers. When [the live pages](/log/live-pages-with-mercure) arrived, a change that raised no event showed up straight away, because the page didn't move, and the missing events went in where the change was made. And when the code needs to change, the rule it touches is in one place, with its tests beside it.
