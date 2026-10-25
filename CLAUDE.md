# CLAUDE.md

This file provides guidance to Claude Code when working with this repository: the rules, each linked to the doc with the detail. [Docs/README.md](Docs/README.md) lists every doc.

## Commands

```bash
# Development (preferred)
symfony serve -d              # Start local dev server (https://127.0.0.1:8000)
make up-dev-tools             # Start DB, Redis, Mailpit, phpMyAdmin (ports and DB name in .env)

# Tests
make test                     # Run all tests in parallel with ParaTest (Docker, one DB per process)
make test-SomeTest            # Run filtered tests
vendor/bin/phpunit            # Run tests locally (ensure test DB exists)

# Code Quality
make check                    # Every check CI runs, same commands (Docker); make check-<step> for one
vendor/bin/php-cs-fixer fix   # @Symfony rules, yoda_style: false
vendor/bin/phpstan analyse    # Level 7
vendor/bin/rector process     # Sets and rules in rector.php
vendor/bin/deptrac analyse    # Rules between bounded contexts (deptrac.yaml)

# Migrations
symfony console make:migration        # Generate migration from entity changes
symfony console doctrine:migrations:migrate  # Run pending migrations
# Schema changes go through migrations only — never doctrine:schema:update or hand-written SQL

# Docker (alternative to symfony serve)
make up / make down / make bash

# Async messaging: symfony serve starts the async worker (.symfony.local.yaml); symfony server:log shows it
symfony console messenger:consume async -vv   # A second, verbose worker, for debugging
```

Local URLs and ports: [Docs/02-setup-local.md](Docs/02-setup-local.md#development-urls). What each `app:` command does: [Docs/09-cli-reference.md](Docs/09-cli-reference.md).

## Project Overview

SupplyMars is a Mars-themed e-commerce and operations platform — PHP 8.5+ / Symfony 8.1.x, Doctrine ORM (MySQL 8.4), Symfony Messenger on a database queue (async; RabbitMQ is an opt-in), Redis (cache, sessions), Tailwind CSS + Turbo (Hotwire), Shadcn kit Twig components (Symfony UX Toolkit), Symfony Asset Mapper (no Webpack/Vite), Zenstruck Foundry + DAMA Doctrine Test Bundle for testing.

Architecturally: a **modular monolith with strong DDD influences**. Domain map: [Docs/01-overview.md](Docs/01-overview.md).

## Architecture

### Bounded Contexts

```
src/
├── Audit/       - Audit logging (status & stock changes)
├── Catalog/     - Products, categories, manufacturers, subcategories
├── Customer/    - Users, addresses, authentication
├── Home/        - Homepage, operational dashboard
├── Note/        - Support tickets: pools, tickets, messages
├── Order/       - Customer orders and order items
├── Pricing/     - VAT rates, pricing strategies, markup cascades
├── Purchasing/  - Purchase orders, suppliers, supplier products
├── Reporting/   - Dashboards (two-layer aggregation: daily records + summaries)
├── Review/      - Product reviews, moderation, summaries
└── Shared/      - Shared kernel (cross-cutting concerns)
```

### DDD Layers (per context)

```text
{Context}/
├── Application/    Command/ Handler/ Listener/ Search/ Service/
├── Domain/         Model/{Aggregate}/ (entity, typed IDs, enums, Event/) Repository/ (interfaces)
├── Infrastructure/ Persistence/Doctrine/ Security/ Mailer/ Factory/ …
└── UI/             Http/ (controllers, forms, Api/) Console/ Twig/
```

A context has only the directories it needs. `Home` has no `Application/` or `Domain/`; only Reporting and Shared have a `Domain/Service/`. The exceptions are listed in [Docs/03-architecture.md](Docs/03-architecture.md#layers).

- **Domain is not framework-free.** Doctrine/Gedmo mapping, `#[Assert\...]` constraints, `repositoryClass` and `#[AsAlias]` on repository interfaces are allowed. Business rules stay in plain methods.
- **Between contexts** (enforced by Deptrac, `deptrac.yaml`): a context may use another context's `Domain/` — entities and Doctrine relations to them, enums, events, repository interfaces. Never its `Application/`, `Infrastructure/` or `UI/` (handlers, services, Doctrine repository classes, controllers, factories). Need another context's query? Type-hint its repository interface and add the method there. `Shared` imports from no business context. Named exceptions, each its own Deptrac layer: `Current user` (`CurrentUserProvider`, `DefaultUserAuthenticator`, usable everywhere), and `Order sourcing` and `Demo customers` (usable from Order only). Fix a failing import rather than adding an exception; there is no `skip_violations` list. See [Docs/03-architecture.md](Docs/03-architecture.md#rules-between-contexts) and [ADR 014](Docs/adr/014-architecture-rules.md).
- **Route names and event cases are not seen by Deptrac.** `HelpController::ROUTE_MAP` and `DomainEventType` (both in Shared) name business routes and events in strings; update them by hand on a rename.

### Key Domain Complexity

These areas have non-obvious design. Read the ADR before modifying, and [Docs/04-sourcing-and-pricing.md](Docs/04-sourcing-and-pricing.md) for how the code behaves today:

- **Multi-supplier sourcing** ([ADR 001](Docs/adr/001-multi-supplier-sourcing.md)): products aggregate several supplier products. Best source: lowest cost, then highest stock.
- **Order line splitting** ([ADR 002](Docs/adr/002-order-line-splitting.md)): automatic allocation puts a line's whole outstanding quantity on one supplier or skips it; a line splits only through manual PO items and refunds. Status derives from child PO items.
- **Simulation-first** ([ADR 003](Docs/adr/003-simulation-first-design.md)): console commands drive the order/purchasing/fulfilment lifecycle with realistic timing.
- **Pricing cascades** ([ADR 004](Docs/adr/004-pricing-abstraction.md)): three-level markup (Product → Subcategory → Category), event-driven recalculation, `PriceModel` rounding (7 cases including `NONE`), `bcmath` precision.
- **Two-layer reporting** ([ADR 005](Docs/adr/005-reporting-strategy.md), [ADR 010](Docs/adr/010-customer-insights-reporting.md)): daily granular records + pre-computed summaries.
- **FormFlow** ([ADR 006](Docs/adr/006-formflow-controller-pattern.md)): standardised controller pattern, in the external `myvars/form-flow` package (below).

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

- `DomainEventInterface` (sync) / `AsyncDomainEventInterface` (queued on the `async` Messenger transport).
- Use `DomainEventProviderTrait` in aggregates.
- Rule: must succeed immediately → sync. Can fail/retry/lag → async. See [Docs/03-architecture.md](Docs/03-architecture.md#domain-events).

### FormFlow (Controller Pattern)

The flows live in the external **`myvars/form-flow`** package (namespace `MyVars\FormFlow\`, `^1.4`, `FormFlowBundle`). It is **not** in `src/`; do not re-add an in-app `FormFlow` directory. The app provides the **adapters**: `Result`, `RedirectTarget`, `FlashMessenger` and `Application\Search\SearchCriteriaInterface` implement or extend the package's `Contract\` ports (autowired; `FlasherInterface` is aliased in `services.yaml`). The package's design-neutral templates are overridden by the app's own at `templates/shared/form_flow/*`. Controllers are thin orchestrators using 5 flow types:

| Flow | Purpose |
|------|---------|
| `FormFlow` | Create/update with Symfony forms |
| `ActionFlow` | State transitions (approve, close, etc.): POST-only, CSRF-checked via `->csrf()` |
| `ConfirmFlow` | Confirm-then-execute (delete, cancel, rewind, remove…) with per-action CSRF |
| `SearchFlow` | Paginated index pages (takes a Pagerfanta adapter) |
| `InlineEditFlow` | Inline field editing via Turbo Frames (the `onSave` callback owns persistence + flush; `property:` on the context checks the entity's rules first) |

- **Mappers** are `__invoke` callables: form DTO → command. Located in `{Context}/UI/Http/Form/Mapper/`, named `{Action}{Entity}Mapper`.
- **Filter mappers**: `SearchCriteria` → `FilterCommand` (readonly DTO implementing `SearchCriteriaInterface`); the handler builds the redirect with `FilterParamBuilder`.
- `FlowContext` factories `forCreate()`, `forUpdate()`, `forFilter()`, `forSearch()`, `forAction()`, `forConfirm()`, `forDelete()`; chainable `->template()`, `->successRoute()`, `->allowDelete(true)`, `->csrf()`. See [Docs/patterns/FormFlow/README.md](Docs/patterns/FormFlow/README.md).

### REST API

- API controllers (`{Context}/UI/Http/Api/`, extending `AbstractApiController`) reuse the web's handlers and repositories, with no domain logic. Resource DTOs have `fromEntity()` + `toArray()`; errors are RFC 7807 Problem Details; document each endpoint with `#[OA\...]` (`/api/doc`).
- Auth is a Bearer token, stored only as a SHA-256 hash (`app:user:issue-token <email>`). See [Docs/patterns/API/README.md](Docs/patterns/API/README.md) and [ADR 011](Docs/adr/011-rest-api-layer.md).

### Roles

`ROLE_ADMIN` is staff; `ROLE_SUPER_ADMIN` includes it. Check with `isGranted()` / `is_granted()`, never `getRoles()`, which ignores the hierarchy. Super-admin-only actions (five deletes: product, supplier, supplier product, VAT rate, pool; editing a staff account; creating an order by hand) are listed in [Docs/08-security.md](Docs/08-security.md#what-only-a-super-admin-can-do); other deletes are open to any staff user.

### Monitoring

- **Error tracking**: Sentry, `prod` only. It reports uncaught exceptions (not errors a command catches and logs) when `SENTRY_DSN` is set, nothing when it is empty.
- **`/health`**: public JSON, 200 or 503. To check another dependency, add a `HealthCheckInterface` class in `src/Shared/Infrastructure/Health/`; it is picked up automatically.
- **`/health/backup`**: fails when no backup has landed for 26 hours. Kept out of `/health` on purpose, because `/health` gates the php container's healthcheck and deploys; a missed backup must not mark the stack down.
- See [Docs/13-monitoring.md](Docs/13-monitoring.md).

### Deployment

- One image: FrankenPHP, worker mode in production, classic mode in development. The `php` container serves the site and `messenger` runs the queue from it ([ADR 016](Docs/adr/016-frankenphp-in-place-of-nginx-and-php-fpm.md)).
- CI builds the production images (`php`, which `messenger` also runs, and `reset`) and pushes them to GHCR, tagged with the commit SHA. The server pulls the images of its checked-out commit with `make up-prod-live` / `make up-prod-playground`; it never builds. Rollback is `git reset --hard <previous-sha> && make up-prod-live && make up-prod-playground`.
- **Scheduled jobs** are listed in `src/Shared/Infrastructure/Scheduler/MainSchedule.php` (no cron, except the playground `reset` container's). Only the production `messenger` container consumes `scheduler_default`, one job at a time; dev and `make up-prod-local` consume only `async`. See [Docs/06-operations.md](Docs/06-operations.md#scheduled-jobs) and [ADR 015](Docs/adr/015-symfony-scheduler-in-place-of-cron.md).
- Application secrets are in the env files outside the repository (`../supplymars-secrets/`), never GitHub secrets. Do not read that directory.
- See [Docs/11-runbook.md](Docs/11-runbook.md); the playground (`PLAYGROUND_MODE`): [Docs/infrastructure/playground.md](Docs/infrastructure/playground.md).

### Route Naming

- **Web**: `app_{context}_{entity}_{action}`, e.g. `app_catalog_manufacturer_index`. When the entity is the context, the entity part is dropped: `app_order_index`, `app_customer_show`, `app_review_approve`.
- **API**: the web name with `api_` in place of `app_`: `api_catalog_product_index`, `api_order_index`.
- **Exceptions**: Audit's log and history routes, and Order's add-PO-item route, are named after the record they show (`app_order_status_log`, `app_catalog_product_history`, `app_purchase_order_*`); Reporting uses `app_reports_*`; pages outside an entity have plain names (`app_login`, `app_homepage`, `app_health`).

## Frontend

Details: [Docs/patterns/UI/README.md](Docs/patterns/UI/README.md), [Docs/patterns/Turbo/README.md](Docs/patterns/Turbo/README.md), [ADR 012](Docs/adr/012-shadcn-ui-kit.md).

- **Turbo Frames**: `<turbo-frame id="body">` for main content, `<turbo-frame id="modal">` for dialogs.
- **Modals**: native `<dialog>` + `basic_modal` Stimulus controller; links use `data-turbo-frame="modal"`. Layout decision in `modal_base.html.twig`. Modals stay Turbo-driven (`basic_modal`, `ModalPanel`, `ConfirmDialog`); the kit's `<twig:Dialog>` / `<twig:AlertDialog>` are only for content already on the page.
- **State-changing actions** are a one-button POST form with a CSRF token, never a GET link ([Docs/patterns/FormFlow/README.md](Docs/patterns/FormFlow/README.md#a-state-change-without-a-form)). A GET link with a side effect (logout) MUST have `data-turbo-prefetch="false"`; a link that only opens a confirm page or modal does not need it ([ADR 007](Docs/adr/007-turbo-frame-modal-architecture.md)).
- **Check what exists first**: Twig components in `src/Shared/UI/Twig/Components/` and `templates/components/`, Stimulus controllers in `assets/controllers/` ([list](Docs/patterns/UI/StimulusControllers.md)). Prefer existing components and styling.
- **UI kit first.** General-purpose primitives come from the Shadcn kit of `symfony/ux-toolkit`, copied into `templates/components/` under the kit's names (`<twig:Button>`, `<twig:Field>`, `<twig:Input>`, `<twig:Card>`, `<twig:Dialog>`…); they are project files, edit them directly. Need a new one (tabs, tooltip, combobox…)? `symfony console ux:install <recipe> --kit=shadcn`; never hand-build it or convert a Flowbite example. Flowbite is not used; do not re-add it. The command asks before overwriting; when updating a recipe, keep the local changes listed in ADR 012.
- **After `ux:install` replaces or adds a template at an existing path**, run `rm -rf var/cache/dev var/cache/test`: installed files keep an old modification time, so Twig otherwise serves the previous compiled template.
- **The kit owns the generic names.** In-house components get descriptive names: `EntityCard` (record card, deliberately not built on the kit's `Card`), `EntityCardFooter`, `ModalPanel`. Never give an in-house component a name the kit uses.
- **Wrappers over repetition.** A repeated composition of kit components gets a one-line in-house wrapper built from the kit's pieces, never parallel markup. Existing: `Callout` (on `Alert`), `PageBreadcrumb` (`Breadcrumb`), `ResultsPagination` (`Pagination`), `FlashToast` (`Toast`), `EmptyState` (`Empty`), `StatusBadge` and `ProfitBadge` (`Badge`), `DataTable` (`Table`). `Skeleton`, `Spinner`, `Avatar`, `Kbd`, `InputGroup` are used directly. Use the wrapper for the common case and the kit's tags for anything it does not cover.
- **Buttons**: `<twig:Button>` is the only button. Variants `default`, `secondary`, `secondary-solid`, `outline`, `ghost`, `link`, `destructive`, `destructive-solid`, `destructive-outline`, `warning`, `success`; `as="a"` for links. Fixed height, does not shrink: `flex-1` (not `w-full`) for buttons sharing a row, `h-auto` for a taller one, `size-*` on icons inside. It is `type="button"` unless given `type="submit"`. The header bar is dark in both modes, so ghost buttons on it pin their colours.
- **Forms** render through `templates/form/shadcn_theme.html.twig`; change a field kind there, not per template. A calendar-picked date uses `DatePickerType`. Opt out with `{% form_theme form with ['form_div_layout.html.twig'] only %}` (see the inline edit form). See [Docs/patterns/UI/Forms.md](Docs/patterns/UI/Forms.md).
- **Colours**: kit components use role tokens from `assets/styles/shadcn.css`; everything else uses literal Tailwind classes, except where a token is an exact match (`border-border`, `bg-popover`, `text-foreground`, `text-muted-foreground`). An in-house link or button that is not a kit component gets `focus-ring`. See [Docs/patterns/UI/Styling.md](Docs/patterns/UI/Styling.md).
- **Flash messages**: `addFlash('success'|'info'|'warning'|'danger', …)`. `base.html.twig` mounts one toaster, `<twig:Sonner id="flash-container">`, and `_flashes.html.twig` renders each flash as `<twig:FlashToast>`, on page load and in Turbo Streams that `append` to `flash-container`. Do not add a second toast mechanism.
- **Live updates (Mercure)**: to tell another user something, `UserNotifierInterface::notify()` (a toast on whatever page they have open); to make a page update for everyone viewing it, pass `live_topics` to its template, mark the action `#[Cache(noStore: true)]`, and queue one `refresh()` per page from the worker (`RefreshOrderPageForViewers`). Every update is private and fire-and-forget; publish from a message handler, never the request. A form, inline edit or kit component that opens, newly placed on a live page, needs its own protection from the morph (`live_open`, `basic_modal`). See [Docs/patterns/Turbo/README.md](Docs/patterns/Turbo/README.md#live-updates-with-mercure) and [ADR 017](Docs/adr/017-live-updates-with-mercure.md).
- **Side panels** (sidebar menu, help panel) are the kit's `<twig:Sheet>`; **menus** use `<twig:DropdownMenu>`. See [Docs/patterns/UI/StimulusControllers.md](Docs/patterns/UI/StimulusControllers.md#side-panels).
- **Contextual help**: add the route to `HelpController::ROUTE_MAP` and a template in `templates/help/` extending `help/_layout.html.twig`.
- Icons via Symfony UX Icons. Charts via Chart.js (Symfony UX). Template naming: [Docs/patterns/Templates/README.md](Docs/patterns/Templates/README.md).

## Testing

Details: [Docs/07-testing.md](Docs/07-testing.md).

- A feature isn't done until a test exercises it the way a caller would — an HTTP request for a controller, a handler call for a handler — not just "it didn't throw".
- Tests mirror `src/` in `tests/`; DAMA rolls back each test's transaction. Foundry factories in `tests/Shared/Factory/` (`Factories` trait).
- Auth: `#[WithStory(StaffUserStory::class)]` or `UserFactory::new()->asStaff()->create()`. For super-admin-only actions (the 08 table): `#[WithStory(SuperAdminUserStory::class)]` or `->asSuperAdmin()`.
- Flow tests use the `HasBrowser` trait (Zenstruck Browser), named `{Feature}FlowTest.php` in `tests/{Context}/UI/`.
- `make test` runs in parallel (ParaTest, one database per process), so a test must not depend on another's files or order. Badge counts are in the app cache; login throttling and rate-limit counts are in their own `cache.rate_limiter` pool (`ArrayAdapter` in tests); both are in memory in tests and start empty on each kernel boot; the browser reboots the kernel per request, so a test that needs counts to carry between requests uses `disableReboot()` (`RateLimitTest`). See [Docs/07-testing.md](Docs/07-testing.md#in-parallel).
- The `async` transport is `in-memory://?serialize=true` in tests. To check a message was queued, read `getSent()` on `messenger.transport.async` without following the redirect first: [Docs/07-testing.md](Docs/07-testing.md#assert-a-message-was-queued). To handle it as the worker would and read what reached the Mercure hub: `QueuedNotifications`.

## Code Style

- Readonly properties where possible; constructor property promotion.
- No Yoda conditions (`$value === null`, not `null === $value`).
- `declare(strict_types=1);` in every PHP file (PHP-CS-Fixer enforces it).
- Full type hints on all methods.
- String concatenation: `'foo' . $bar` (space around `.`).
- Rich entities with domain logic; thin controllers (delegate to Flow/handlers).
- Use existing Form model / type / mapper patterns.
- Use repositories for all queries.
- **No request's data in a service property.** Production runs FrankenPHP in worker mode, so one set of services serves many requests, as the Messenger worker's serves many messages. Read the user, request or session when needed (`CurrentUserProvider`, `RequestStack`); never keep them, or anything derived from them, in a property. A service that caches on purpose implements `ResetInterface` and clears itself in `reset()` (`PublicIdResolverRegistry`). `StateBetweenRequestsTest` guards this. See [Docs/03-architecture.md](Docs/03-architecture.md#state-between-requests).

## Symfony Conventions

- **Attributes and autowiring, not config**: follow the surrounding code (`#[Route]`, `#[AsCommand]`, `#[AsEventListener]`, `#[Autowire(param:/env:)]`, `#[Target]`). A YAML service or route definition is the last resort.
- **Readonly services**: don't mark a service class `readonly` if it might become lazy — a lazy proxy can't extend a `readonly` class. DTOs, commands and value objects are fine.
- **New capabilities via Flex**: `composer require <package>` and let the recipe register the bundle and base config. Don't hand-edit `config/bundles.php` or hand-write a bundle's base config.
- **Use the framework before building or importing**: check for a Symfony component before hand-writing infrastructure (locks, caches, HTTP clients, schedulers) or adding a third-party library — e.g. `symfony/lock` for mutual exclusion, never a hand-built flag or lock file (`BackupDatabaseCommand` shows a scheduled command locked with `LockableTrait`; see [Docs/06-operations.md](Docs/06-operations.md#scheduled-commands-of-your-own)).
- **Makers**: pass every argument up front with `--no-interaction` where supported; makers prompt by default, which hangs a non-interactive shell. If a maker still needs input, hand-write the code.

### Discover, don't guess

Framework APIs change between versions. Look things up in the project rather than relying on memory:

- `symfony console debug:router`, `debug:container`, `debug:autowiring <name>`, `debug:config <bundle>`, `config:dump-reference <bundle>` — what exists and how it is configured.
- `symfony console lint:container`, `lint:twig templates/`, `lint:yaml config/` — validate before running.
- When something fails, read `var/log/dev.log` and the web profiler (`/_profiler`) before changing code.
- Read the installed source under `vendor/`, and use docs matching the version in `composer.json`.

## New Feature Checklist

1. Determine the bounded context (existing or new — if new, register in `config/packages/doctrine.yaml` and add its layers to `deptrac.yaml`, as [Docs/03-architecture.md](Docs/03-architecture.md#rules-between-contexts) lists).
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

- [Docs/README.md](Docs/README.md) — every doc and when to read it: guides, patterns (`Docs/patterns/`), ADRs (`Docs/adr/`) and the user manual. Authoritative; consult before proposing new designs.
