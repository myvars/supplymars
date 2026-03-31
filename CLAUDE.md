# CLAUDE.md

This file provides guidance to Claude Code when working with this repository.

## Commands

```bash
# Development (preferred)
symfony serve -d              # Start local dev server (https://127.0.0.1:8000)
make up-dev-tools             # Start DB, Redis, RabbitMQ, Mailpit

# Tests
make test                     # Run all tests (Docker, handles DB setup)
make test-SomeTest            # Run filtered tests
vendor/bin/phpunit            # Run tests locally (ensure test DB exists)

# Code Quality
vendor/bin/php-cs-fixer fix   # @Symfony rules, yoda_style: false
vendor/bin/phpstan analyse    # Level 7
vendor/bin/rector process     # Dead code, type declarations, Doctrine/Symfony sets

# Migrations
symfony console make:migration        # Generate migration from entity changes
symfony console doctrine:migrations:migrate  # Run pending migrations

# Docker (alternative to symfony serve)
make up / make down / make bash

# Async messaging
symfony console messenger:consume async
```

## Project Overview

SupplyMars is a Mars-themed e-commerce and operations platform — PHP 8.5+ / Symfony 8.0.x, Doctrine ORM (MySQL 8.4), RabbitMQ (async), Redis (cache), Tailwind CSS + Turbo (Hotwire), Symfony Asset Mapper (no Webpack/Vite), Zenstruck Foundry + DAMA Doctrine Test Bundle for testing.

Architecturally: a **modular monolith with strong DDD influences**.

## Architecture

### Bounded Contexts

```
src/
├── Audit/       - Audit logging (status & stock changes)
├── Catalog/     - Products, categories, manufacturers, subcategories
├── Customer/    - Users, addresses, authentication
├── Home/        - Homepage, operational dashboard
├── Order/       - Customer orders and order items
├── Pricing/     - VAT rates, pricing strategies, markup cascades
├── Purchasing/  - Purchase orders, suppliers, supplier products
├── Reporting/   - Dashboards (two-layer aggregation: daily records + summaries)
├── Review/      - Product reviews, moderation, summaries
└── Shared/      - Shared kernel (cross-cutting concerns)
```

### DDD Layers (per context)

```
{Context}/
├── Application/    Command/ Handler/ Listener/ Search/ Service/
├── Domain/         Model/ Repository/ Event/ Service/
├── Infrastructure/ Persistence/Doctrine/
└── UI/             Http/ (Controllers, Forms, DTOs)
```

### Key Domain Complexity

These areas have non-obvious design. Read the corresponding ADR in `Docs/adr/` before modifying:

- **Multi-supplier sourcing** (ADR-001): Products aggregate multiple SupplierProducts. Best-source algorithm picks lowest cost, then highest stock.
- **Order line splitting** (ADR-002): Single order items split across suppliers by outstanding quantity. Status derives from child PO items.
- **Simulation-first** (ADR-003): Console commands drive the full order/purchasing/fulfilment lifecycle with realistic timing.
- **Pricing cascades** (ADR-004): Three-level markup (Product → Subcategory → Category) with event-driven recalculation, 6 price models, `bcmath` precision.
- **Two-layer reporting** (ADR-005): Daily granular records + pre-computed summaries for fast dashboards.
- **FormFlow** (ADR-006): Standardized controller pattern. Full spec in `Docs/patterns/FormFlow/`.

## Patterns

### Entities

- PHP attribute mapping (`#[ORM\...]`), not XML/YAML.
- **ULID Public IDs**: Auto-increment `id` internally, ULID `publicId` for URLs. Use `HasPublicUlid` trait, call `$this->initializePublicId()` in constructor. Each entity has a typed `{Entity}PublicId` value object extending `AbstractUlidId`.
- **ValueResolver**: `#[ValueResolver('public_id')]` resolves entities by ULID in controllers.
- **Timestampable**: `TimestampableEntity` trait (Gedmo) for `createdAt`/`updatedAt`.

### CQRS

- **Commands** (`Application/Command/`) — Readonly DTOs for writes.
- **Handlers** (`Application/Handler/`) — One per command, return `Result::ok()` or `Result::fail()`.
- **Search** (`Application/Search/`) — Query criteria for reads (extend `SearchCriteria` with `SORT_OPTIONS`, `SORT_DEFAULT`, `LIMIT_DEFAULT`).

### Domain Events

- `DomainEventInterface` (sync) / `AsyncDomainEventInterface` (RabbitMQ).
- Use `DomainEventProviderTrait` in aggregates.
- Rule: must succeed immediately → sync. Can fail/retry/lag → async.

### FormFlow (Controller Pattern)

Controllers are thin orchestrators. 4 flow types:

| Flow | Purpose |
|------|---------|
| `FormFlow` | Create/update with Symfony forms |
| `CommandFlow` | State transitions (approve, reject, etc.) |
| `DeleteFlow` | Delete with confirmation |
| `SearchFlow` | Paginated index pages |

- **Mappers** are `__invoke` callables: form DTO → command. Located in `{Context}/UI/Http/Form/Mapper/`, named `{Action}{Entity}Mapper`.
- **Filter mappers**: `SearchCriteria` → `FilterCommand` (readonly DTO implementing `SearchCriteriaInterface`). Handler builds redirect via `FilterParamBuilder`.
- `FlowContext` factories: `forCreate()`, `forUpdate()`, `forFilter()`, `forSearch()`, `forCommand()`.
- Chainable: `->template()`, `->successRoute()`, `->allowDelete(true)`, `->redirectOptions(refresh: true)`.

### Route Naming

`app_{context}_{entity}_{action}` — e.g., `app_catalog_manufacturer_index`.

## Frontend

- **Turbo Frames**: `<turbo-frame id="body">` for main content, `<turbo-frame id="modal">` for dialogs.
- **Modals**: Native `<dialog>` + `basic_modal` Stimulus controller. Links use `data-turbo-frame="modal"`. Layout decision in `modal_base.html.twig`.
- **State-changing links/actions** MUST have `data-turbo-prefetch="false"`.
- **Twig Components** in `src/Shared/UI/Twig/Components/` and `templates/components/` — check existing components before creating new ones.
- **Stimulus Controllers** in `assets/controllers/` — check existing controllers before creating new ones.
- Icons via Symfony UX Icons. Charts via Chart.js (Symfony UX).
- Prefer existing UI components, Twig components, and styling patterns where possible to maintain site-wide consistency.

## Testing

- Tests use `APP_ENV=test` + DAMA Doctrine Test Bundle (transaction rollback per test).
- Tests mirror `src/` structure in `tests/`.
- Factories in `tests/Shared/Factory/` (Zenstruck Foundry). Use `Factories` trait in test classes.
- Auth: `#[WithStory(StaffUserStory::class)]` or `UserFactory::new()->asStaff()->create()`. For delete handlers: `#[WithStory(SuperAdminUserStory::class)]` or `UserFactory::new()->asSuperAdmin()->create()`.
- Flow tests use `HasBrowser` trait (Zenstruck Browser). Named `{Feature}FlowTest.php` in `tests/{Context}/UI/`.

## Code Style

- Readonly properties where possible; constructor property promotion.
- No Yoda conditions (`$value === null`, not `null === $value`).
- Full type hints on all methods.
- String concatenation: `'foo' . $bar` (space around `.`).
- Rich entities with domain logic; thin controllers (delegate to Flow/handlers).
- Use existing Form model / type / mapper patterns.
- Use repositories for all queries.

## New Feature Checklist

1. Determine the bounded context (existing or new — if new, register in `config/packages/doctrine.yaml`).
2. Follow DDD layers: Commands/Handlers in `Application/`, Entities/VOs in `Domain/Model/`, Repository interfaces in `Domain/Repository/`, Doctrine repos in `Infrastructure/Persistence/`, Controllers/Forms in `UI/Http/`.
3. Create typed `{Entity}PublicId` extending `AbstractUlidId`.
4. Use `HasPublicUlid` trait, call `initializePublicId()` in constructor.
5. Place tests in corresponding `tests/` subdirectory.
6. Create Foundry factory in `tests/Shared/Factory/` if needed.

## Workflow

- Always produce a clear implementation plan **before writing code**.
- Research docs (`Docs/adr/`, `Docs/patterns/`) before proposing designs. Cite file paths for every claim.
- Consider "do nothing" as a valid option — say so if the current code already handles it.
- For non-trivial tasks, state: objective, what changes, options considered, and blocking questions.
- If assumptions would materially affect design or complexity, **stop and ask first**.
- Commit messages: clear, concise, written as a human contributor. Do not mention AI as a contributor.

## Reference

- `Docs/` — Authoritative technical and user documentation. Consult before proposing new designs.
- `Docs/adr/` — Architecture Decision Records explaining key design choices.
- `Docs/patterns/` — Detailed pattern specifications (FormFlow, etc.).
