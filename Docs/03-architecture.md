# System Architecture

## Module / Folder Structure

SupplyMars follows a **modular monolith** architecture with **Domain-Driven Design (DDD)** principles. Each bounded context is organized as a self-contained module with its own layers, allowing the codebase to evolve without tight coupling between domains.

### Why a Modular Monolith?

The architecture strikes a balance between simplicity and future flexibility. Contexts often communicate through domain events and well-defined interfaces rather than direct database queries or shared entities. This means that if scaling requirements change, individual bounded contexts (such as Reporting or Purchasing) can be extracted into separate services with minimal refactoring - the integration boundaries are already in place.

```
src/
├── Audit/                    # Change tracking and logging
├── Catalog/                  # Products, categories, manufacturers
├── Customer/                 # Users, addresses, authentication
├── Home/                     # Homepage (simple, no DDD layers)
├── Order/                    # Customer orders and order items
├── Pricing/                  # VAT rates, pricing listeners
├── Purchasing/               # Suppliers, supplier products, purchase orders
├── Reporting/                # Sales reporting and dashboards
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
    └── Console/              # CLI commands
```

## Layering Rules

### UI Layer

**Responsibility:** Handle HTTP requests, console commands, render views.

**Allowed dependencies:** Application layer only.

**Key components:**
- Controllers (thin, delegate to FormFlow or handlers)
- Forms (Symfony form types)
- Console commands
- Twig templates

**Rule:** Controllers must not contain business logic. They orchestrate via FormFlow classes.

### Application Layer

**Responsibility:** Coordinate use cases, handle cross-cutting concerns.

**Allowed dependencies:** Domain layer.

**Key components:**
- Commands (readonly DTOs representing operations)
- Handlers (process commands, return Result objects)
- Listeners (respond to domain events)
- Search criteria (read model queries)

**Rule:** Application layer orchestrates but doesn't contain domain rules.

### Domain Layer

**Responsibility:** Business rules, domain logic, entity behavior.

**Allowed dependencies:** None (pure domain).

**Key components:**
- Entities (with behavior, not anemic)
- Value Objects (immutable, self-validating)
- Repository interfaces (not implementations)
- Domain events (record what happened)
- Domain services (logic that doesn't fit entities)

**Rule:** Domain layer has no infrastructure dependencies.

### Infrastructure Layer

**Responsibility:** Implement domain interfaces, external integrations.

**Allowed dependencies:** Domain layer interfaces.

**Key components:**
- Doctrine repositories (implement domain interfaces)
- File storage adapters
- External service clients

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
│    └── Async events → MessageBus (RabbitMQ)                            │
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
| FormFlow | `FormFlow`, `CommandFlow`, `DeleteFlow`, `SearchFlow` | `src/Shared/UI/Http/FormFlow/` |
| Mapper | Context-specific mappers | `src/{Context}/UI/Http/Form/Mapper/` |
| Handler | Context-specific handlers | `src/{Context}/Application/Handler/` |
| Repository | Domain interfaces + Doctrine impl | `src/{Context}/Domain/Repository/` + `Infrastructure/Persistence/` |
| EventDispatcher | `DomainEventDispatcher` | `src/Shared/Infrastructure/Persistence/Doctrine/EventListener/` |

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
│  • Collects events from flushed entities                               │
│  • Dispatches sync events via EventDispatcher                          │
│  • Dispatches async events via MessageBus                              │
└────────────────────────────────────────────────────────────────────────┘
                    │                              │
                    ▼                              ▼
        ┌─────────────────────┐      ┌─────────────────────────────────┐
        │   SYNC LISTENERS    │      │        ASYNC (RABBITMQ)         │
        │  (same transaction) │      │  • AsyncDomainEventInterface    │
        │                     │      │  • Processed by messenger:      │
        │  • Pricing cascade  │      │    consume async                │
        │  • Audit logging    │      │  • Email notifications          │
        └─────────────────────┘      └─────────────────────────────────┘
```

### Event Types

| Event Interface | Dispatch | Use Case |
|-----------------|----------|----------|
| `DomainEventInterface` | Synchronous | Pricing cascades, audit logging |
| `AsyncDomainEventInterface` | RabbitMQ | Email, external notifications |

**Key events:**
- `SupplierProductPricingWasChangedEvent` → Recalculates product pricing
- `CategoryPricingWasChangedEvent` → Cascades to category products
- `PurchaseOrderStatusWasChangedEvent` → Updates order item status
- `OrderStatusWasChangedEvent` → Audit logging
- `ReviewWasCreatedEvent` → Recalculates product review summary
- `ReviewStatusWasChangedEvent` → Recalculates product review summary
- `ReviewRatingWasChangedEvent` → Recalculates product review summary

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

Production cron schedule (inferred from `docker/php/cron/prod-crontab`):

| Interval | Command | Purpose |
|----------|---------|---------|
| */5 min | `app:create-customer-orders 2 --random` | Steady order flow |
| */15 min | `app:accept-purchase-orders 20` | Supplier responses |
| */30 min | `app:build-purchase-orders 20` | Allocation catchup |
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
| `src/Shared/UI/Http/FormFlow/Redirect/TurboAwareRedirector.php` | Stream generation |

See [Turbo Patterns](patterns/Turbo/README.md) and [UI Patterns](patterns/UI/README.md) for detailed documentation
