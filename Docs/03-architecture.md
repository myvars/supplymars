# System Architecture

The rules the code follows: layers, what one context may use from another, state between requests, domain events and the queue, plus where to start reading for each area. Read it before adding a class to a context you have not worked in, a listener, a service that holds data, or a new context.

## Layers

The app is a modular monolith: one deployable, split into bounded contexts (listed in [01-overview.md](01-overview.md)) and a shared kernel (`Shared`). The rules below keep the work of extracting a context into a service bounded; they do not remove it.

```text
src/{Context}/
├── Application/      Command/ Handler/ Listener/ Search/ Service/
├── Domain/           Model/{Aggregate}/ (entity, typed IDs, enums, Event/)  Repository/ (interfaces)
├── Infrastructure/   Persistence/Doctrine/  Security/  Mailer/  Factory/ …
└── UI/               Http/ (controllers, forms, Api/)  Console/  Twig/
```

A context has only the directories it needs. Where the tree differs:

| Where | What |
|-------|------|
| Domain events | In `Domain/Model/{Aggregate}/Event/` (e.g. `src/Order/Domain/Model/Order/Event/`). Only `Shared` has `Domain/Event/`: the interfaces, `DomainEventProviderTrait` and `DomainEventType` |
| `Domain/Service/` | Only `src/Reporting/Domain/Service/` (`SalesDateRangeResolver`) and `src/Shared/Domain/Service/Pricing/` (`MarkupCalculator`). Add one only for a rule that belongs to no single entity |
| `Infrastructure/` | `Security/` (Customer: `CurrentUserProvider`, `ApiTokenHandler`; Shared), `Mailer/` (Customer), `Factory/` (Customer's demo-customer factories), `Logging/` (Audit). `Shared` also has `FileStorage/`, `Health/` and `Scheduler/` |
| `UI/Http/Api/` | Catalog and Order (`Resource/`; Order also `Payload/` for input), base classes in `src/Shared/UI/Http/Api/`. See [API](patterns/API/README.md) |
| Odd ones | Reporting has `Application/Report/` and `Domain/Metric/` |
| `Home` | No `Application/` or `Domain/`: just `Listener/`, `Service/` and `UI/`. One Deptrac layer, `Home internals`, that nothing else may use |

| Layer | May depend on | Rule |
|-------|---------------|------|
| UI | Application, Domain | Controllers hold no business logic: they hand off to a flow, a handler or (API) a repository |
| Application | Domain, framework services (`Security`, mailer, cache) | Orchestrates; the business rules stay in the domain |
| Domain | `Shared`, other contexts' `Domain/`, mapping metadata | See below |
| Infrastructure | Domain, Doctrine and other libraries | Implements the domain's interfaces |

Within a context these are conventions, checked in review. Between contexts they are enforced (next section).

The domain is not framework-free. These are allowed:
- Doctrine mapping attributes and collections, and Gedmo's `TimestampableEntity`, on entities
- Symfony Validator constraints (`#[Assert\...]`, `#[UniqueEntity]`) on entities
- Symfony `Uid` for public IDs, and `UserInterface` on the `User` entity
- The entity's own Doctrine repository, named in `#[ORM\Entity(repositoryClass: ...)]`, and on the repository interface in `#[AsAlias(...)]`, which tells the container which class implements it
- `FindByCriteriaInterface` (`src/Shared/Infrastructure/Persistence/Search/`) as a parent of a repository interface, for repositories that back a search page

These are attributes and type declarations. Domain methods hold the business rules and do not call the framework, the database or other services.

A web request runs: route and firewall → `PublicIdResolver` (`#[ValueResolver('public_id')]`) loads the entity by its ULID → the controller hands off to a flow → a mapper turns the form DTO into a command → the handler calls domain methods, flushes and returns a `Result`. See [FormFlow](patterns/FormFlow/README.md). API requests skip the flows: see [API](patterns/API/README.md).

## Rules Between Contexts

Each context has a public part, its `Domain/` directory, and internals: `Application/`, `Infrastructure/` and `UI/`.

| From | May use | Must not use |
|------|---------|--------------|
| A business context | Its own code, `Shared`, any other context's `Domain/` (entities, enums, value objects, events, repository interfaces) | Another context's `Application/`, `Infrastructure/` or `UI/`: its handlers, services, Doctrine repositories, controllers, forms, factories |
| `Shared` | `Shared` only | Any business context |

Examples from this codebase:

- `ProductSales` (Reporting) has a Doctrine relation to `Product` (Catalog). Allowed: `Product` is in Catalog's `Domain/`.
- `CalculateProductSalesHandler` (Reporting) loads products through `ProductRepository` (Catalog). Allowed: it is the repository *interface*, in Catalog's `Domain/`. When a context needs a query the interface does not offer yet, add the method to the interface.
- Type-hinting `ProductDoctrineRepository` there instead would fail the build. The Doctrine class is Catalog's `Infrastructure/`.
- `StatusChangeLogger` (Audit) listens to `OrderStatusWasChangedEvent` (Order). Allowed: events are in `Domain/`.

**Named exceptions.** A few classes are published by their context on purpose. Each has a Deptrac layer of its own, and only the contexts listed may use it:

| Layer | Classes | Owner | Used by | Why |
|-------|---------|-------|---------|-----|
| `Current user` | `CurrentUserProvider`, `DefaultUserAuthenticator` (`src/Customer/Infrastructure/Security/`) | Customer | Every context | Every context needs the signed-in user; the simulation commands sign in the default staff user |
| `Order sourcing` | `OrderAllocator`, `CreatePurchaseOrderItem` and its handler (`src/Purchasing/Application/`) | Purchasing | Order | Sourcing an order from suppliers is Purchasing's job, started from Order: allocating a whole order, or adding a purchase order item from the order screen |
| `Demo customers` | `RandomUserFactory`, `RandomAddressFactory` (`src/Customer/Infrastructure/Factory/`) | Customer | Order | Demo orders are placed by simulated customers |

When an import fails the check, fix it: use the other context's `Domain/`, add the query to its repository interface, or move the class to the context that owns it. Add a named exception only for a deliberate dependency on another context's behaviour: give it a layer of its own in `deptrac.yaml`, open only to the contexts that need it, and a row in the table above. There is no `skip_violations` list; an entry there would let any later import of the same class through without a reason on record.

**What Deptrac cannot see:** references made only by name in a string. `HelpController::ROUTE_MAP` (`src/Shared/UI/Http/Controller/HelpController.php`) maps business route names to help pages, and `DomainEventType` (`src/Shared/Domain/Event/`) has a case per business event. Both live in Shared on purpose, as registries the contexts fill in. Renaming a route or an event means updating them by hand.

**Enforcement.** `deptrac.yaml` splits each context into `{Context} Domain` and `{Context} internals` (Home has only `Home internals`). `vendor/bin/deptrac analyse` fails on any import the tables do not allow, in CI and in `make check` (`make check-deptrac`). A new context needs, in `deptrac.yaml`, its two layers, its two ruleset entries (`[+Public, …]`), its Domain layer in the `Public` list and its name in the `Unregistered` pattern; and a mapping in `config/packages/doctrine.yaml`. Until it is in `deptrac.yaml` its classes fall into the `Unregistered` layer, which may use nothing and which nothing may use, so the first import to or from the new context fails the build. See [ADR 014](adr/014-architecture-rules.md) for the decision.

## State Between Requests

In production FrankenPHP runs in worker mode: each worker starts the app once and serves up to 500 requests with the same services before it restarts ([ADR 016](adr/016-frankenphp-in-place-of-nginx-and-php-fpm.md)). The Messenger worker already worked this way, between messages, and the scheduled jobs run inside it. A service that keeps a request's data in a property therefore hands it to the next request, which may be another user's.

**The rule:** a service keeps no data from one request or message in a property. Read the current user, the request and the session when they are needed (`CurrentUserProvider`, `Security`, `RequestStack`) rather than storing them or anything worked out from them.

A service that caches on purpose implements `Symfony\Contracts\Service\ResetInterface` and clears the cache in `reset()`, as `PublicIdResolverRegistry` does. Autoconfiguration tags it `kernel.reset`, and Symfony then calls `reset()` between requests, and the Messenger worker between messages.

| Fine | Not fine |
|------|----------|
| Injected services and configuration, in `readonly` properties | `private ?User $user`, filled on first use |
| A property used within one call and reset when the call starts (`DomainEventDispatcher` resets its list of deleted entities in `onFlush`, on every flush) | An array of lookups kept "for this request", with no `reset()` |
| A value in the app cache with an expiry, keyed by user when it is per user (`SidebarBadgeProvider`'s "My Queue" count, `KEY_MY_QUEUE_PREFIX` + user id) | A static property holding data |
| Twig components: a new instance each time they render | |

**The guard:** `tests/Shared/UI/Http/StateBetweenRequestsTest.php` keeps one kernel across requests (`disableReboot()`), as a worker does, and interleaves two logged-in staff users and a visitor. Each page must show only its own user in the header and their own "My Queue" count, each status change must be logged against the user who made it, and the visitor must be sent to the login page. Other tests boot a fresh kernel per request and do not see a leak. The app cache is an in-memory adapter in tests, emptied between requests, so the test guards services, not cache keys: a value cached per user must still carry the user in its key. A feature that shows or records per-user data through a new service extends this test.

Development (`symfony serve`, `make up`) runs in classic mode, where every request starts afresh, so a leak shows only in production, under `make up-prod-local` and in tests that keep one kernel.

## Domain Events

- An entity records events with `DomainEventProviderTrait` (`raiseDomainEvent()`) in a domain method; nothing is dispatched then.
- After each flush, `DomainEventDispatcher` (`src/Shared/Infrastructure/Persistence/Doctrine/EventListener/`) releases the events of every managed entity that records them, and of entities the flush deleted, and then dispatches each to the event dispatcher (sync listeners). All are released before any is dispatched: a listener that flushes runs this again, and must find nothing left, or a flush of many entities nests one level per entity (the stock commands hit Xdebug's 512-frame limit that way). So an event a listener raises and flushes is dispatched, in that nested run, before the rest of the outer flush's events.
- Events implementing `AsyncDomainEventInterface` are also sent to the `async` Messenger transport. No event does today.

| Interface | Runs | Use when |
|-----------|------|----------|
| `DomainEventInterface` | Synchronously, in the same request, after the flush | It must succeed immediately (pricing, audit log, cache invalidation) |
| `AsyncDomainEventInterface` | Also queued on the `async` transport | It can fail, retry or lag (email, external calls) |

Who listens (`#[AsEventListener]`):

| Listener | Events | Does |
|----------|--------|------|
| `src/Pricing/Application/Listener/` (one class per event) | `CategoryPricingWasChangedEvent`, `SubcategoryPricingWasChangedEvent`, `VatRateWasChangedEvent`, `SupplierStatusWasChangedEvent`, `SupplierProductStatusWasChangedEvent`, `SupplierProductPricingWasChangedEvent` | Reprices the affected products: see [04-sourcing-and-pricing.md](04-sourcing-and-pricing.md) |
| `StatusChangeLogger` (`src/Audit/Application/Listener/`) | Order, order item, PO and PO item status changes | Writes the status change log |
| `SupplierStockChangeLogger` (same directory) | `SupplierProductStockWasChangedEvent` | Writes the stock change log. Repricing comes from the `SupplierProductPricingWasChangedEvent` raised beside it |
| `ReviewSummaryUpdater` (`src/Review/Application/Listener/`) | The four `Review*` events | Recalculates the product's review summary |
| `SidebarBadgeCacheInvalidator` (`src/Home/Listener/`) | Review created, status changed, deleted; PO status changed; order created, status changed | Deletes the sidebar badge counts that changed |
| `ProductImageWasDeleted` (`src/Catalog/Application/Listener/`) | `ProductImageWasDeletedEvent` | Deletes the image file and its cached thumbnails |
| `RefreshOrderPageForViewers` (`src/Order/Application/Listener/`) | Order status and lock changes; order items created, changed, removed or with a status change; POs created, rewound or with a status change; PO items created, changed, removed or with a status change | Queues one live refresh of the order's page, and its purchase orders' pages: see [ADR 017](adr/017-live-updates-with-mercure.md) |
| `NotifyLockHolderOfRelease` (`src/Order/Application/Listener/`) | `OrderLockWasChangedEvent` | Queues a toast for a user who lost their lock to someone else |

## The Queue

Outgoing email and async events are routed to the `async` Messenger transport (`config/packages/messenger.yaml`). By default that is the Doctrine transport: messages are rows in the `messenger_messages` table, in the application's own database, and the `messenger` container (under `symfony serve`, the worker the server starts) works through them. Failed messages go to the `failed` transport, in the same table. No domain event is async today: the queue carries email, live page refreshes and lock-release toasts.

In the `test` environment the transport is `in-memory://`: nothing is stored, no worker runs, and a test can read what was sent ([07-testing.md](07-testing.md#assert-a-message-was-queued)).

RabbitMQ is kept as an opt-in for when the database queue is outgrown. See [ADR 013](adr/013-database-queue-by-default.md) for the decision and [Switching to RabbitMQ](02-setup-local.md#switching-to-rabbitmq) for the steps.

Scheduled jobs run in the same `messenger` container, from `scheduler_default`: see [06-operations.md](06-operations.md#scheduled-jobs).

## Where to Start

Reading order for the areas whose logic spans several files.

| To understand | Read, in order |
|---------------|----------------|
| Pricing | `src/Shared/Domain/Service/Pricing/MarkupCalculator.php`, `src/Shared/Domain/ValueObject/PriceModel.php`, `src/Catalog/Domain/Model/Product/Product.php` (`getActiveMarkup()`, `recalculatePrice()`), `src/Pricing/Application/Listener/` |
| Order allocation | `src/Purchasing/Application/Service/OrderAllocator.php`, `src/Purchasing/Application/Service/OrderItemAllocator.php`, `Product::getBestSourceWithMinQuantity()`, `src/Order/Domain/Model/Order/CustomerOrderItem.php` (`getOutstandingQty()`) |
| Status flows | `src/Order/Domain/Model/Order/OrderStatus.php`, `src/Purchasing/Domain/Model/PurchaseOrder/PurchaseOrderStatus.php` (`canTransitionTo()`), then `generateStatus()` on `CustomerOrder`, `CustomerOrderItem` and `PurchaseOrder` |
| Reviews | `src/Review/Domain/Model/Review/ProductReview.php`, `ReviewStatus.php`, `src/Review/Application/Listener/ReviewSummaryUpdater.php`, `ProductReview::ineligibility()` (who may review) |
| Support tickets | `src/Note/Domain/Model/Ticket/Ticket.php` (transitions, snooze), `TicketStatus.php`, `src/Note/Domain/Model/Message/Message.php` (visibility, author type), `src/Note/Domain/Model/Pool/Pool.php` |
| REST API | `src/Shared/UI/Http/Api/AbstractApiController.php`, `ApiResponse.php`, `src/Shared/UI/Http/Api/EventListener/ApiExceptionListener.php`, `src/Customer/Infrastructure/Security/ApiTokenHandler.php`, then `src/{Context}/UI/Http/Api/` |

## Gotchas

- **A deleted entity's event fires only if it is `remove()`d and flushed.** The dispatcher notes entities scheduled for deletion in `onFlush`, before the flush drops them from the identity map. An entity that raises a deletion event but is never removed still dispatches it on the next flush, and its listeners act on a row that still exists. Always follow the domain method with `remove()` and a flush (`DeleteProductImageHandler`).
- **Events fire on every flush:** through `FlusherInterface` (`DoctrineFlusher`), a direct `EntityManager::flush()`, fixtures and Foundry factories alike.
- **Events fire after the commit.** A sync listener that throws does not undo the change (unless the flush runs inside an outer transaction), but the exception still reaches the caller: the change is saved and the request fails.

## Related

- [ADR 014](adr/014-architecture-rules.md): rules between contexts
- [ADR 016](adr/016-frankenphp-in-place-of-nginx-and-php-fpm.md): worker mode
- [ADR 013](adr/013-database-queue-by-default.md): the database queue
- [FormFlow](patterns/FormFlow/README.md): the request flow in controllers
- [API](patterns/API/README.md): the REST API flow, auth and errors
- [Turbo](patterns/Turbo/README.md): frames, modals and streams
- [04-sourcing-and-pricing.md](04-sourcing-and-pricing.md): suppliers, best source, allocation, pricing cascade, PO lifecycle
- [09-cli-reference.md](09-cli-reference.md): the simulation commands and what each does
