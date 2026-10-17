# System Architecture

## Module / Folder Structure

SupplyMars follows a **modular monolith** architecture with **Domain-Driven Design (DDD)** principles. Each bounded context is organized as a self-contained module with its own layers, allowing the codebase to evolve without tight coupling between domains.

### Why a Modular Monolith?

The architecture strikes a balance between simplicity and future flexibility. A context may use another context's domain model (its entities, value objects, events and repository interfaces), so a `ProductSales` record in Reporting can point to a Catalog `Product` through an ordinary Doctrine relation. It never reaches into another context's handlers, Doctrine repositories or controllers, and the shared kernel depends on no business context. Deptrac checks these rules in CI (see [Rules between contexts](#rules-between-contexts)); the imports that still break them are listed as exceptions, each with its reason. Extracting a context such as Reporting into a separate service would still mean replacing those relations and repository calls with an API: the rules keep that work bounded, they do not remove it.

```
src/
├── Audit/                    # Change tracking and logging
├── Catalog/                  # Products, categories, manufacturers
├── Customer/                 # Users, addresses, authentication
├── Home/                     # Homepage, operational dashboard
├── Order/                    # Customer orders and order items
├── Pricing/                  # VAT rates, pricing listeners
├── Purchasing/               # Suppliers, supplier products, purchase orders
├── Reporting/                # Sales reporting and dashboards
├── Note/                     # Support pools, tickets, internal messaging
├── Review/                   # Product reviews and moderation
└── Shared/                   # Cross-cutting concerns (Shared Kernel)
```

Each bounded context follows a consistent internal structure:

```
{Context}/
├── Application/              # Use cases, orchestration
│   ├── Command/              # Write operation DTOs (readonly)
│   ├── Handler/              # Command processors
│   ├── Listener/             # Domain event subscribers
│   ├── Search/               # Read model criteria
│   └── Service/              # Application services
├── Domain/                   # Business logic
│   ├── Model/                # Entities, Aggregates, Value Objects
│   ├── Repository/           # Repository interfaces
│   ├── Event/                # Domain events
│   └── Service/              # Domain services
├── Infrastructure/           # External concerns
│   ├── Persistence/          # Doctrine repositories
│   └── Factory/              # Object creation utilities
└── UI/                       # Presentation
    ├── Http/                 # Controllers, Forms, DTOs
    │   └── Api/              # API controllers, Resources, Requests
    └── Console/              # CLI commands
```

## Layering Rules

These describe what each layer is for and what it depends on in practice. Within a context they are conventions, checked in review. Between contexts they are enforced (see [Rules between contexts](#rules-between-contexts)).

### UI Layer

**Responsibility:** Handle HTTP requests, console commands, render views, serve API responses.

**Depends on:** Application (commands, handlers, search criteria) and Domain (entities resolved from the route, repository interfaces for read-only lookups).

**Key components:**
- Controllers (thin, delegate to FormFlow or handlers)
- Forms (Symfony form types)
- Console commands
- Twig templates
- API controllers (JSON endpoints, delegate to handlers/repositories)
- API Resource classes (readonly DTOs for JSON serialization)
- API Request classes (validated input DTOs)

**Rule:** Controllers must not contain business logic. Web controllers orchestrate via FormFlow; API controllers use handlers directly and return `ApiResponse` helpers.

### Application Layer

**Responsibility:** Coordinate use cases, handle cross-cutting concerns.

**Depends on:** Domain, and framework services (`Security`, mailer, cache).

**Key components:**
- Commands (readonly DTOs representing operations)
- Handlers (process commands, return Result objects)
- Listeners (respond to domain events)
- Search criteria (read model queries)

**Rule:** Application layer orchestrates but doesn't contain domain rules.

### Domain Layer

**Responsibility:** Business rules, domain logic, entity behavior.

**Depends on:** Shared, other contexts' Domain layers, and mapping metadata. The domain is not framework-free. These are allowed:
- Doctrine mapping attributes and collections, and Gedmo's `TimestampableEntity`, on entities
- Symfony Validator constraints (`#[Assert\...]`, `#[UniqueEntity]`) on entities
- Symfony `Uid` for public IDs, and `UserInterface` on the `User` entity
- The entity's own Doctrine repository, named in `#[ORM\Entity(repositoryClass: ...)]`, and on the repository interface in `#[AsAlias(...)]`, which tells the container which class implements it
- `FindByCriteriaInterface` (`src/Shared/Infrastructure/Persistence/Search/`) as a parent of a repository interface, for repositories that back a search page

These are attributes and type declarations. Domain methods hold the business rules and do not call the framework, the database or other services.

**Key components:**
- Entities (with behavior, not anemic)
- Value Objects (immutable, self-validating)
- Repository interfaces (not implementations)
- Domain events (record what happened)
- Domain services (logic that doesn't fit entities)

### Infrastructure Layer

**Responsibility:** Implement domain interfaces, external integrations.

**Depends on:** Domain (the interfaces it implements, the entities it stores), Doctrine and other libraries.

**Key components:**
- Doctrine repositories (implement domain interfaces)
- File storage adapters
- External service clients

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

### Enforcement

`deptrac.yaml` splits each context into two layers, `{Context} Domain` and `{Context} internals`, and lists which layers each may use. `vendor/bin/deptrac analyse` fails on any import the table above does not allow. It runs in CI and in `make check` (`make check-deptrac`).

There is no `skip_violations` list. When an import fails the check, fix it: use the other context's `Domain/`, add the query to its repository interface, or move the class to the context that owns it. Add a named exception only for a deliberate dependency on another context's behaviour, as its own layer in `deptrac.yaml`, open only to the contexts that need it, and add it to the table above.

A new context must be added to `deptrac.yaml`: its two layers, its two ruleset entries (`[+Public, …]`), its `Domain` layer in the list of the `Public` entry, and its name in the `Unregistered` pattern. Until then its classes fall into the `Unregistered` layer, which may use nothing and which nothing may use, so the first import to or from the new context fails the build. See [ADR 014](adr/014-architecture-rules.md) for the decision.

## Request Flow

### HTTP Request → Response

```
┌────────────────────────────────────────────────────────────────────────┐
│                            HTTP REQUEST                                │
└────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│                          SYMFONY KERNEL                                │
│  • Route matching                                                      │
│  • Security checks (firewall)                                          │
│  • Parameter conversion (ValueResolver)                                │
└────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│                            CONTROLLER                                  │
│  • Receives Request + resolved entities                                │
│  • Delegates to FormFlow (create/update/delete/search)                 │
│  • Returns Response                                                    │
└────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│                             FORMFLOW                                   │
│  • Handles form lifecycle (GET/POST)                                   │
│  • Validates form data                                                 │
│  • Invokes Mapper to create Command                                    │
│  • Invokes Handler to process Command                                  │
│  • Returns Response (redirect or re-render)                            │
└────────────────────────────────────────────────────────────────────────┘
                                   │
                          ┌────────┴────────┐
                          ▼                 ▼
┌─────────────────────────────┐  ┌───────────────────────────────────────┐
│           MAPPER            │  │                HANDLER                │
│  • Form DTO → Command DTO   │  │  • Loads entities via Repository      │
│  • Value object creation    │  │  • Invokes domain methods             │
│                             │  │  • Validates with Symfony Validator   │
└─────────────────────────────┘  │  • Persists via Flusher               │
                                 │  • Returns Result (ok/fail)           │
                                 └───────────────────────────────────────┘
                                            │
                                            ▼
┌────────────────────────────────────────────────────────────────────────┐
│                          DOMAIN LAYER                                  │
│  • Entity methods enforce business rules                               │
│  • Domain events raised (collected, not dispatched)                    │
│  • Value objects validate on construction                              │
└────────────────────────────────────────────────────────────────────────┘
                                            │
                                            ▼
┌────────────────────────────────────────────────────────────────────────┐
│                         INFRASTRUCTURE                                 │
│  • EntityManager flush()                                               │
│  • DomainEventDispatcher (postFlush listener)                          │
│    ├── Sync events → EventDispatcher                                   │
│    └── Async events → MessageBus (database queue)                      │
└────────────────────────────────────────────────────────────────────────┘
                                            │
                                            ▼
┌────────────────────────────────────────────────────────────────────────┐
│                          HTTP RESPONSE                                 │
│  • 303 redirect (success)                                              │
│  • 422 re-render (validation failure)                                  │
│  • Turbo Stream response (if Turbo request)                            │
└────────────────────────────────────────────────────────────────────────┘
```

### Key Classes in the Flow

| Step | Class | Location |
|------|-------|----------|
| ValueResolver | `PublicIdResolver` | `src/Shared/UI/Http/ValueResolver/` |
| FormFlow | `FormFlow`, `ActionFlow`, `ConfirmFlow`, `SearchFlow` | `vendor/myvars/form-flow/src/` |
| Mapper | Context-specific mappers | `src/{Context}/UI/Http/Form/Mapper/` |
| Handler | Context-specific handlers | `src/{Context}/Application/Handler/` |
| Repository | Domain interfaces + Doctrine impl | `src/{Context}/Domain/Repository/` + `Infrastructure/Persistence/` |
| EventDispatcher | `DomainEventDispatcher` | `src/Shared/Infrastructure/Persistence/Doctrine/EventListener/` |

### API Request → Response

API endpoints follow a simpler flow than FormFlow. Controllers receive JSON input, delegate to existing handlers or repositories, and return JSON via `ApiResponse`.

```
┌────────────────────────────────────────────────────────────────────────┐
│                            HTTP REQUEST                                │
│  Authorization: Bearer <token>                                         │
│  Content-Type: application/json                                        │
└────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│                          SYMFONY KERNEL                                │
│  • Route matching (/api/v1/...)                                        │
│  • API firewall (stateless, Bearer token via ApiTokenHandler)          │
│  • MapRequestPayload / MapQueryString for input DTOs                   │
└────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│                        API CONTROLLER                                  │
│  • Extends AbstractApiController                                       │
│  • Receives validated Request DTO or SearchCriteria                    │
│  • Delegates to Handler (writes) or Repository (reads)                 │
│  • Transforms result via Resource class                                │
│  • Returns ApiResponse (item / collection / error / noContent)         │
└────────────────────────────────────────────────────────────────────────┘
                          │                    │
                          ▼                    ▼
              ┌───────────────────┐  ┌──────────────────────────────────┐
              │   RESOURCE (DTO)  │  │           HANDLER                │
              │  Entity → array   │  │  (same as web: domain logic,     │
              │  for JSON output  │  │   returns Result ok/fail)        │
              └───────────────────┘  └──────────────────────────────────┘
                                               │
                                               ▼
              ┌────────────────────────────────────────────────────────────┐
              │                    JSON RESPONSE                           │
              │  • 200 { "data": {...} }              (single item)        │
              │  • 200 { "data": [...], "meta": {}, "links": {} }          │
              │  • 201 { "data": {...} }              (created)            │
              │  • 204                                (no content)         │
              │  • 4xx/5xx RFC 7807 problem+json      (errors)             │
              └────────────────────────────────────────────────────────────┘
```

**On error**, `ApiExceptionListener` intercepts exceptions for `/api/` routes and returns RFC 7807 problem+json responses. Validation errors include a `violations` array.

### Key API Classes

| Class | Location | Purpose |
|-------|----------|---------|
| AbstractApiController | `src/Shared/UI/Http/Api/` | Base controller with `handleResult()` and `resolveFilterId()` |
| ApiResponse | `src/Shared/UI/Http/Api/` | Static factory for JSON responses |
| ApiExceptionListener | `src/Shared/UI/Http/Api/EventListener/` | Converts exceptions to RFC 7807 JSON |
| ApiTokenHandler | `src/Customer/Infrastructure/Security/` | Validates Bearer tokens |
| Resource classes | `src/{Context}/UI/Http/Api/Resource/` | Entity → JSON transformation |
| Request classes | `src/{Context}/UI/Http/Api/Request/` | Validated input DTOs |

See [API Pattern](patterns/API/README.md) for implementation details and [ADR 011](adr/011-rest-api-layer.md) for the architectural decision.

## Supplier Model

### Entity Relationships

```
┌─────────────────┐
│    Supplier     │
│─────────────────│
│ name            │
│ isActive        │
│ isWarehouse     │──────┐
└─────────────────┘      │
        │                │
        │ 1:N            │
        ▼                │
┌─────────────────┐      │
│ SupplierProduct │      │
│─────────────────│      │
│ name            │      │
│ productCode     │      │
│ mfrPartNumber   │      │
│ cost            │      │
│ stock           │      │
│ leadTimeDays    │      │
│ isActive        │      │
└─────────────────┘      │
        │                │
        │ N:1            │
        ▼                │
┌─────────────────┐      │
│    Product      │◄─────┘ (warehouse = primary)
│─────────────────│
│ name            │
│ cost (derived)  │
│ sellPrice       │
│ sellPriceIncVat │
│ stock (derived) │
└─────────────────┘
```

### Supplier Types

**Warehouse Supplier:**
- `isWarehouse = true`
- Primary inventory source
- Single warehouse per system (enforced by configuration)
- Products created from warehouse first

**EDI/Dropship Suppliers:**
- `isWarehouse = false`
- External fulfillment partners
- May have overlapping products with warehouse
- Costs and stock fluctuate independently

### Best Source Selection

Products select their "best" supplier via `calculateBestActiveSource()`:

```php
// src/Catalog/Domain/Model/Product/Product.php

private function calculateBestActiveSource(): ?SupplierProduct
{
    $sources = $this->getActiveSupplierProducts();

    // Filter: must have stock and positive cost
    $viable = $sources->filter(fn($sp) =>
        $sp->hasStock() && $sp->hasPositiveCost()
    );

    // Sort by cost ASC, then stock DESC
    // Returns lowest cost; if tied, highest stock wins
}
```

## Pricing Model

### Markup Hierarchy

Pricing follows a three-level inheritance:

```
┌─────────────────────────────────────────────────┐
│                    PRODUCT                      │
│  defaultMarkup: 0 (no override)                 │
│  priceModel: NONE (no override)                 │
│                    │                            │
│                    │ inherits if 0/NONE         │
│                    ▼                            │
├─────────────────────────────────────────────────┤
│                  SUBCATEGORY                    │
│  defaultMarkup: 0 (no override) or 15.000       │
│  priceModel: NONE (no override) or PRETTY_99    │
│                    │                            │
│                    │ inherits if 0/NONE         │
│                    ▼                            │
├─────────────────────────────────────────────────┤
│                   CATEGORY                      │
│  defaultMarkup: 5.000 (always set)              │
│  priceModel: DEFAULT (always set)               │
│  vatRate: 20.00% (Standard Rate)                │
└─────────────────────────────────────────────────┘
```

### Price Calculation

```
Final Price = PriceModel.getPrettyPrice(
    cost × (1 + markup/100) × (1 + vatRate/100)
)
```

All calculations use `bcmath` for precision:
- `src/Shared/Domain/Service/Pricing/MarkupCalculator.php`

### Price Models (Pretty Rounding)

| Model | Effect | Example |
|-------|--------|---------|
| `NONE` | No rounding | 25.67 → 25.67 |
| `DEFAULT` | No rounding | 25.67 → 25.67 |
| `PRETTY_00` | Round to .00 | 25.67 → 26.00 |
| `PRETTY_10` | Round to .10 | 25.67 → 25.70 |
| `PRETTY_49` | Round to .49/.99 | 25.50+ → 25.99 |
| `PRETTY_95` | Round to .95 | 25.67 → 25.95 |
| `PRETTY_99` | Round to .99 | 25.67 → 25.99 |

## Order Sourcing Logic

### Allocation Process

When an order is allocated to suppliers:

```
┌────────────────────────────────────────────────────────────────────────┐
│                       OrderAllocator.process()                         │
└────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│             For each CustomerOrderItem with outstanding qty            │
└────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│     Product.getBestSourceWithMinQuantity(outstandingQty)               │
│     → Returns SupplierProduct with lowest cost + sufficient stock      │
└────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│     EditablePurchaseOrderProvider.getOrCreateForSupplier()             │
│     → Returns existing editable PO or creates new one                  │
└────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│     OrderItemAllocator.forOrderItem()                                  │
│     → Creates PurchaseOrderItem for outstanding quantity               │
│     → Recalculates PO totals                                           │
└────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│     Update item/order statuses: PENDING → PROCESSING                   │
└────────────────────────────────────────────────────────────────────────┘
```

**Key files:**
- `src/Purchasing/Application/Service/OrderAllocator.php`
- `src/Purchasing/Application/Service/OrderItemAllocator.php`
- `src/Purchasing/Application/Service/EditablePurchaseOrderProvider.php`

### Purchase Order Lifecycle

```
     PENDING ──────────────────────────────────────────┐
        │                                              │
        ▼                                              │
   PROCESSING ─────────────────────────────────────────┤
        │                                              │
        ├─────────► ACCEPTED                           │
        │              │                               │
        │              ├─────► SHIPPED                 │
        │              │          │                    │
        │              │          ▼                    │
        │              │      DELIVERED                │
        │              │                               │
        │              └─────► REJECTED                │
        │                          │                   │
        │                          ▼                   │
        │                      REFUNDED                │
        │                                              │
        └──────────────────────► CANCELLED ◄───────────┘
```

## Eventing / Async Processing

### Domain Event Infrastructure

Events are collected during entity operations and dispatched after persistence:

```php
// Entity raises event
class Product implements DomainEventProviderInterface
{
    use DomainEventProviderTrait;

    public function changePricing(...): void
    {
        // ... business logic ...
        $this->raiseDomainEvent(new ProductPricingWasChangedEvent(...));
    }
}
```

### Event Dispatch Flow

```
┌────────────────────────────────────────────────────────────────────────┐
│                       EntityManager::flush()                           │
└────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│              DomainEventDispatcher (postFlush listener)                │
│  • Collects events from flushed entities, and from entities the        │
│    flush deleted                                                       │
│  • Dispatches sync events via EventDispatcher                          │
│  • Dispatches async events via MessageBus                              │
└────────────────────────────────────────────────────────────────────────┘
                    │                              │
                    ▼                              ▼
        ┌─────────────────────┐      ┌─────────────────────────────────┐
        │   SYNC LISTENERS    │      │     ASYNC (DATABASE QUEUE)      │
        │  (after the commit) │      │  • AsyncDomainEventInterface    │
        │                     │      │  • Processed by messenger:      │
        │  • Pricing cascade  │      │    consume async                │
        │  • Audit logging    │      │  • Email notifications          │
        └─────────────────────┘      └─────────────────────────────────┘
```

An entity that is deleted can raise an event too: the dispatcher notes the entities scheduled for deletion in `onFlush`, before the flush takes them out of the identity map, and dispatches their events with the rest. Raise the event in a domain method, and always follow it with `remove()` and a flush. An entity that raises a deletion event but is never removed still dispatches it on the next flush, and its listeners act on a row that still exists:

```php
$productImage->delete();                 // raises ProductImageWasDeletedEvent
$this->productImages->remove($productImage);
$this->flusher->flush();                 // the event is dispatched after the delete
```

Events are dispatched for every flush, whether it goes through the `Flusher`, a direct `EntityManager::flush()`, fixtures or Foundry factories. They are dispatched after the flush commits. A sync listener that throws does not undo the change, unless the flush runs inside an outer transaction, but the exception still reaches the caller: the change is saved even though the request fails.

### Event Types

| Event Interface | Dispatch | Use Case |
|-----------------|----------|----------|
| `DomainEventInterface` | Synchronous | Pricing cascades, audit logging |
| `AsyncDomainEventInterface` | Queued on the `async` Messenger transport | Email, external notifications |

**Key events:**
- `SupplierProductPricingWasChangedEvent` → Recalculates product pricing
- `CategoryPricingWasChangedEvent` → Cascades to category products
- `PurchaseOrderStatusWasChangedEvent` → Updates order item status
- `OrderStatusWasChangedEvent` → Audit logging
- `ReviewWasCreatedEvent` → Recalculates product review summary
- `ReviewStatusWasChangedEvent` → Recalculates product review summary
- `ReviewRatingWasChangedEvent` → Recalculates product review summary
- `ReviewWasDeletedEvent` → Recalculates product review summary, clears the pending-review badge
- `ProductImageWasDeletedEvent` → Removes the image file from storage and its cached thumbnails

### The Queue

Outgoing email and async events are routed to the `async` Messenger transport (`config/packages/messenger.yaml`). By default that is the Doctrine transport: messages are rows in the `messenger_messages` table, in the application's own database, and the `messenger` container (or `symfony console messenger:consume async`) works through them. Failed messages go to the `failed` transport, which uses the same table. No domain event is async today, so the queue carries email only.

In the `test` environment the transport is `in-memory://`: nothing is stored, no worker runs, and a test can read what was sent.

RabbitMQ is kept as an opt-in for when the database queue is outgrown. See [ADR 013](adr/013-database-queue-by-default.md) for the decision and [Switching to RabbitMQ](02-setup-local.md#switching-to-rabbitmq) for the steps.

## Simulation Boundaries

### What Is Simulated

| Component | Simulation | Reality |
|-----------|------------|---------|
| Customer orders | `app:create-customer-orders` | Would come from storefront |
| Payment processing | Not implemented | Would integrate with payment gateway |
| Supplier acceptance | `app:accept-purchase-orders` (98% accept) | Would be EDI/API response |
| Shipping | `app:ship-purchase-order-items` (time-based) | Would be carrier tracking |
| Delivery | `app:deliver-purchase-order-items` (time-based) | Would be proof of delivery |
| Stock files | `app:update-supplier-stock` (random variance) | Would be EDI stock feeds |
| Pricing feeds | Stock command updates costs | Would be supplier price files |
| Product reviews | `app:generate-reviews` (weighted random) | Would be customer-submitted |

### What Is Real

| Component | Implementation |
|-----------|----------------|
| Order allocation logic | Actual business rules |
| Pricing calculations | Real markup/VAT/rounding |
| Status state machines | Enforced transitions |
| Reporting aggregation | Real SQL queries |
| Authentication/authorization | Full Symfony Security |

### Simulation Timing

Production cron schedule (from `docker/php/cron/live-crontab`):

| Interval | Command | Purpose |
|----------|---------|---------|
| */5 min | `app:create-customer-orders 2 --random` | Steady order flow |
| */15 min | `app:build-purchase-orders 20` | Allocate orders to suppliers |
| +1 min offset | `app:accept-purchase-orders 20` | Supplier responses (runs 1 min after build) |
| Hourly | `app:ship-purchase-order-items 100` | Ship accepted items |
| Hourly | `app:deliver-purchase-order-items 100` | Complete deliveries |
| */15 min | `app:update-supplier-stock 20` | Stock fluctuation |
| Daily 00:03 | `app:calculate-product-sales 1` | Reporting ETL |
| Daily 00:07 | `app:calculate-order-sales 1` | Reporting ETL |

## Frontend Navigation

### Turbo (Hotwire)

SupplyMars uses Hotwire Turbo for SPA-like navigation without a JavaScript framework. See [ADR 007](adr/007-turbo-frame-modal-architecture.md) for the architectural decision.

### Frame Hierarchy

```
┌─────────────────────────────────────────────────────────────────┐
│  <turbo-frame id="body">        Main content, updates URL       │
├─────────────────────────────────────────────────────────────────┤
│  <turbo-frame id="modal">       Dialogs inside <dialog>         │
├─────────────────────────────────────────────────────────────────┤
│  <turbo-frame id="{model}-table">  Search results, updates URL  │
├─────────────────────────────────────────────────────────────────┤
│  <turbo-frame id="reports">     Dashboard widgets               │
└─────────────────────────────────────────────────────────────────┘
```

### Modal System

Forms and confirmations render in a native `<dialog>` element managed by Stimulus:

1. Link with `data-turbo-frame="modal"` triggers fetch
2. Server detects `turbo-frame: modal` header, returns minimal layout
3. Stimulus controller opens dialog on frame load
4. Form submits to same frame, receives Turbo Stream response
5. Controller auto-closes on successful submission

### Turbo Streams

After form submission, the server returns Turbo Streams instead of HTTP redirects:

```html
<turbo-stream action="refresh"></turbo-stream>
<turbo-stream action="append" target="flash-container">...</turbo-stream>
```

The `TurboAwareRedirector` in FormFlow handles this automatically.

### Key Files

| File | Purpose |
|------|---------|
| `assets/controllers/basic_modal_controller.js` | Modal lifecycle |
| `templates/shared/turbo/modal_base.html.twig` | Layout decision |
| `vendor/myvars/form-flow/src/Redirect/TurboAwareRedirector.php` | Stream generation |

See [Turbo Patterns](patterns/Turbo/README.md) and [UI Patterns](patterns/UI/README.md) for detailed documentation
