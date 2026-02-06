# SupplyMars Documentation

Welcome to the technical documentation for SupplyMars. This guide covers architecture, setup, features, and operations for developers and maintainers working with the codebase.

## Who Is This For?

- **Senior engineers** inheriting or extending the codebase
- **Operations staff** managing orders, suppliers, and inventory
- **Architects** evaluating DDD and modular monolith patterns
- **Future maintainers** requiring deep system understanding

For a high-level introduction to the project, see the [main README](../README.md). For domain concepts and what makes the system non-trivial, start with [01-overview.md](01-overview.md).

## High-Level Architecture

SupplyMars is a **modular monolith** - bounded contexts are self-contained modules that mainly communicate through domain events and interfaces. This keeps the benefits of a single deployable unit while preserving clear boundaries, making it straightforward to extract contexts into separate services if scaling demands it.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            PRESENTATION LAYER                               │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐ │
│  │   Twig +    │  │  FormFlow   │  │   Console   │  │  Turbo Frames &     │ │
│  │  Tailwind   │  │ Controllers │  │  Commands   │  │  Streams            │ │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
                                      │
┌─────────────────────────────────────────────────────────────────────────────┐
│                            APPLICATION LAYER                                │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐ │
│  │  Commands   │  │  Handlers   │  │  Listeners  │  │  Search Criteria    │ │
│  │  (DTOs)     │  │             │  │  (Events)   │  │                     │ │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
                                      │
┌─────────────────────────────────────────────────────────────────────────────┐
│                               DOMAIN LAYER                                  │
│  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────────┐  │
│  │  Catalog  │ │   Order   │ │ Purchasing│ │  Pricing  │ │   Reporting   │  │
│  │           │ │           │ │           │ │           │ │               │  │
│  │ Products  │ │  Customer │ │ Suppliers │ │ VAT Rates │ │ Product Sales │  │
│  │ Categories│ │  Orders   │ │ Purchase  │ │ Markups   │ │ Order Sales   │  │
│  │ Subcats   │ │  Order    │ │ Orders    │ │ Price     │ │ Dashboards    │  │
│  │ Mfrs      │ │  Items    │ │ Supplier  │ │ Models    │ │               │  │
│  │           │ │           │ │ Products  │ │           │ │               │  │
│  └───────────┘ └───────────┘ └───────────┘ └───────────┘ └───────────────┘  │
│  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌─────────────────────────────┐  │
│  │ Customer  │ │  Review   │ │   Audit   │ │         Shared Kernel       │  │
│  │           │ │           │ │           │ │  Events, Value Objects,     │  │
│  │ Users     │ │ Product   │ │ Status    │ │  Services, Result, FormFlow │  │
│  │ Addresses │ │ Reviews   │ │ Logs      │ │  ULID IDs, MarkupCalculator │  │
│  │           │ │ Summaries │ │           │ │                             │  │
│  └───────────┘ └───────────┘ └───────────┘ └─────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
                                      │
┌─────────────────────────────────────────────────────────────────────────────┐
│                           INFRASTRUCTURE LAYER                              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐ │
│  │  Doctrine   │  │  RabbitMQ   │  │   Redis     │  │  S3 / Local FS      │ │
│  │  MySQL 8.4  │  │  Messenger  │  │   Cache     │  │  File Storage       │ │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Documentation Index

### Getting Started
- **[01-overview.md](01-overview.md)** - System purpose, domain concepts, what makes this non-trivial
- **[02-setup-local.md](02-setup-local.md)** - Prerequisites, environment setup, running locally

### Technical Deep-Dives
- **[03-architecture.md](03-architecture.md)** - Module structure, request flow, layering rules
- **[04-sourcing-and-pricing.md](04-sourcing-and-pricing.md)** - Multi-supplier sourcing, pricing engine, order splitting
- **[05-features.md](05-features.md)** - Feature-by-feature documentation for developers

### Operations
- **[06-operations.md](06-operations.md)** - Deployment, cron jobs, workers, data simulation
- **[07-testing.md](07-testing.md)** - Test strategy, factories, running tests
- **[08-security.md](08-security.md)** - Authentication, authorization, safety assumptions

### Reference
- **[09-cli-reference.md](09-cli-reference.md)** - All console commands with examples
- **[10-code-reference.md](10-code-reference.md)** - Navigational map of the codebase

### Patterns
- **[patterns/FormFlow/](patterns/FormFlow/README.md)** - Thin controller orchestration for CRUD operations
- **[patterns/Turbo/](patterns/Turbo/README.md)** - Turbo Frames, Streams, and modal system
- **[patterns/UI/](patterns/UI/README.md)** - Server-driven UI with Twig Components, Stimulus, and Forms

### Architecture Decision Records
- **[adr/001-multi-supplier-sourcing.md](adr/001-multi-supplier-sourcing.md)** - Why orders split across suppliers
- **[adr/002-order-line-splitting.md](adr/002-order-line-splitting.md)** - Why order items can have multiple prices
- **[adr/003-simulation-first-design.md](adr/003-simulation-first-design.md)** - Why simulation drives the system
- **[adr/004-pricing-abstraction.md](adr/004-pricing-abstraction.md)** - Hierarchical pricing with pretty-price rounding
- **[adr/005-reporting-strategy.md](adr/005-reporting-strategy.md)** - Two-layer aggregation for performance
- **[adr/006-formflow-controller-pattern.md](adr/006-formflow-controller-pattern.md)** - FormFlow abstraction for thin controllers
- **[adr/007-turbo-frame-modal-architecture.md](adr/007-turbo-frame-modal-architecture.md)** - Turbo Frames with native dialog modals
- **[adr/008-server-driven-ui-architecture.md](adr/008-server-driven-ui-architecture.md)** - Server-driven UI instead of JavaScript SPA

---

## Common Tasks Cheat Sheet

### Development

```bash
# Start local development (preferred)
symfony serve -d                    # PHP server at https://127.0.0.1:8000
make up-dev-tools                   # MySQL, Redis, RabbitMQ, Mailpit, PHPMyAdmin

# Or use full Docker stack
make up                             # All services containerized
make bash                           # Shell into PHP container

# Run tests
make test                           # All tests via Docker
make test-SomeTest                  # Filtered tests
vendor/bin/phpunit                  # Local (ensure test DB exists)

# Code quality (see 07-testing.md for configuration details)
vendor/bin/php-cs-fixer fix         # Fix code style (@Symfony rules)
vendor/bin/phpstan analyse          # Static analysis (level 7)
vendor/bin/rector process           # Automated refactoring
```

### Console Commands (Simulation)

```bash
# Order simulation
symfony console app:create-customer-orders 10      # Create 10 orders
symfony console app:build-purchase-orders 50       # Allocate to suppliers

# Purchase order workflow
symfony console app:accept-purchase-orders 20      # Supplier acceptance
symfony console app:ship-purchase-order-items 50   # Ship items
symfony console app:deliver-purchase-order-items 50 # Deliver items

# Product reviews
symfony console app:generate-reviews 50            # Generate fake reviews

# Stock simulation
symfony console app:update-supplier-stock 50       # Fluctuate stock/costs

# Reporting
symfony console app:calculate-product-sales 7      # Last 7 days
symfony console app:calculate-order-sales 7        # Last 7 days
```

### Local URLs

| Service | URL |
|---------|-----|
| Application | https://localhost:8000 |
| PHPMyAdmin | http://localhost:8080 |
| Mailpit | http://localhost:8025 |
| RabbitMQ Management | http://localhost:15672 |

---

## Key Technologies

| Component | Technology | Purpose |
|-----------|------------|---------|
| Language | PHP 8.5+ | Application code |
| Framework | Symfony 8.0 | HTTP, DI, console, forms |
| Database | MySQL 8.4 | Primary data store |
| ORM | Doctrine | Entity mapping, migrations |
| Queue | RabbitMQ | Async event processing |
| Cache | Redis | Sessions, query cache |
| Frontend | Tailwind CSS + Turbo | Styling, SPA-like UX |
| Testing | PHPUnit + Foundry | Test framework + factories |
| File Storage | S3 / Local | Product images |

---

## Contributing

1. Read the architecture documentation before making changes
2. Follow DDD principles: domain logic in Domain layer, orchestration in Application layer
3. Write tests using Foundry factories
4. Run code quality tools before committing
5. Keep commit messages clear and human-written
