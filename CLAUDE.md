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
# Schema changes go through migrations only — never doctrine:schema:update or hand-written SQL

# Docker (alternative to symfony serve)
make up / make down / make bash

# Async messaging
symfony console messenger:consume async
```

## Project Overview

SupplyMars is a Mars-themed e-commerce and operations platform — PHP 8.5+ / Symfony 8.1.x, Doctrine ORM (MySQL 8.4), RabbitMQ (async), Redis (cache), Tailwind CSS + Turbo (Hotwire), Symfony Asset Mapper (no Webpack/Vite), Zenstruck Foundry + DAMA Doctrine Test Bundle for testing.

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
- **FormFlow** (ADR-006): Standardized controller pattern, now the external `myvars/form-flow` package (`MyVars\FormFlow\`). Full spec in the package `README.md` + `Docs/patterns/FormFlow/`.

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

The flow coordinators live in the external **`myvars/form-flow`** package (namespace `MyVars\FormFlow\`,
required from Packagist as `^1.2`, enabled as `FormFlowBundle`) — **not** in `src/`; do not re-add an
in-app `FormFlow` directory. The app provides the **adapters** — `Result`, `RedirectTarget`,
`FlashMessenger` and `Application\Search\SearchCriteriaInterface` implement/extend the package's
`Contract\` ports (autowired; `FlasherInterface` aliased in `services.yaml`). The package ships
**design-neutral default templates**; this app's own `templates/shared/form_flow/*` override them. Controllers
are thin orchestrators using 5 flow types:

| Flow | Purpose |
|------|---------|
| `FormFlow` | Create/update with Symfony forms |
| `ActionFlow` | State transitions (approve, reject, etc.) |
| `ConfirmFlow` | Confirm-then-execute (delete, cancel, rewind, remove…) with per-action CSRF |
| `SearchFlow` | Paginated index pages (takes a Pagerfanta adapter) |
| `InlineEditFlow` | Inline field editing via Turbo Frames (the `onSave` callback owns persistence + flush) |

- **Mappers** are `__invoke` callables: form DTO → command. Located in `{Context}/UI/Http/Form/Mapper/`, named `{Action}{Entity}Mapper`.
- **Filter mappers**: `SearchCriteria` → `FilterCommand` (readonly DTO implementing `SearchCriteriaInterface`). Handler builds redirect via `FilterParamBuilder`.
- `FlowContext` factories: `forCreate()`, `forUpdate()`, `forFilter()`, `forSearch()`, `forAction()`, `forConfirm()`, `forDelete()`.
- Chainable: `->template()`, `->successRoute()`, `->allowDelete(true)`.

### Route Naming

`app_{context}_{entity}_{action}` — e.g., `app_catalog_manufacturer_index`.

## Frontend

- **Turbo Frames**: `<turbo-frame id="body">` for main content, `<turbo-frame id="modal">` for dialogs.
- **Modals**: Native `<dialog>` + `basic_modal` Stimulus controller. Links use `data-turbo-frame="modal"`. Layout decision in `modal_base.html.twig`.
- **State-changing links/actions** MUST have `data-turbo-prefetch="false"`.
- **Twig Components** in `src/Shared/UI/Twig/Components/` and `templates/components/` — check existing components before creating new ones.
- **Stimulus Controllers** in `assets/controllers/` — check existing controllers before creating new ones.
- **UI kit** (`Docs/adr/012-shadcn-ui-kit.md`): general-purpose primitives come from the Shadcn kit of `symfony/ux-toolkit`, copied into `templates/components/` under the kit's own names — `<twig:Button>`, `<twig:Field>`, `<twig:Input>`, `<twig:Card>`, `<twig:Dialog>`, etc. They are project files — edit them directly.
  - **Need a new primitive** (tabs, tooltip, combobox…)? `symfony console ux:install <recipe> --kit=shadcn` rather than hand-building or converting a Flowbite example. Flowbite is not used; do not re-add it. The command asks before overwriting; when updating a recipe, keep the local changes listed in ADR 012.
  - **The kit owns the generic names.** In-house components get descriptive names: `EntityCard` (record card with edit/show links and status highlight), `EntityCardFooter`, `ModalPanel` (content frame for the Turbo modal). `EntityCard` is a domain component, deliberately not built on the kit's `Card`. Never give an in-house component a name the kit uses.
  - **Wrappers over repetition.** When the same composition of kit components repeats, give it a one-line in-house wrapper built *from the kit's pieces* — never parallel markup. Existing wrappers: `<twig:Callout type="info|success|warning|danger">` (on `Alert`), `<twig:PageBreadcrumb href label current>` (on `Breadcrumb`), `<twig:ResultsPagination :pager>` (on `Pagination`, via the Pagerfanta template), `<twig:FlashToast type :message>` (on `Toast`), `<twig:EmptyState icon message subtitle>` (on `Empty`), `<twig:StatusBadge status>` and `<twig:ProfitBadge :value>` (on `Badge`), `<twig:DataTable title>` (on `Table`). Small pieces with no logic — `Skeleton`, `Spinner`, `Avatar`, `Kbd`, `InputGroup` — are used directly, with no wrapper. Use the wrapper for the common case and the kit's tags directly for anything it does not cover.
  - **After `ux:install` replaces or adds a template at an existing path**, run `rm -rf var/cache/dev var/cache/test`: installed files keep an old modification time, so Twig otherwise serves the previous compiled template.
  - **Buttons**: `<twig:Button>` is the kit's and the only button. Variants: `default`, `secondary`, `secondary-solid`, `outline`, `ghost`, `link`, `destructive`, `destructive-solid`, `destructive-outline`, `warning`, `success`; `as="a"` for links. It has a fixed height and does not shrink — use `flex-1` (not `w-full`) for buttons sharing a row, `h-auto` for a taller one, and `size-*` on icons inside it. It is `type="button"` unless given `type="submit"`. The header bar is dark in both modes, so ghost buttons on it pin their colours.
  - **Forms** render through the global theme `templates/form/shadcn_theme.html.twig`; change a field kind there, not per template. Money and percent fields get their symbol as an `InputGroup` addon; a calendar-picked date uses `DatePickerType`. A form that must not look like standard controls opts out with `{% form_theme form with ['form_div_layout.html.twig'] only %}` (see the inline edit form).
  - **Colours**: kit components use role tokens (`bg-primary`, `bg-card`, `border-input`) defined in `assets/styles/shadcn.css` and mapped to the app's gray/blue scales. Everything else uses literal Tailwind classes, except where a token is an exact match for a border, surface or text colour (`border-border`, `bg-popover`, `text-foreground`, `text-muted-foreground`). An in-house link or button that is not a kit component gets the `focus-ring` utility, so keyboard focus looks the same everywhere.
  - **Modals stay Turbo-driven** (`basic_modal`, `ModalPanel`, `ConfirmDialog`). The kit's `<twig:Dialog>` / `<twig:AlertDialog>` are only for content already on the page.
  - **Flash messages**: `addFlash('success'|'info'|'warning'|'danger', …)` as usual. `base.html.twig` mounts one toaster, `<twig:Sonner id="flash-container">`; `_flashes.html.twig` renders each flash as `<twig:FlashToast>`, on page load and inside Turbo Streams that `append` to `flash-container`. Do not add a second toast mechanism.
  - **Side panels** (the sidebar menu, the help panel) are the kit's `<twig:Sheet>`, a modal `<dialog>` driven by the kit's `dialog` controller. Give the content an inner element that fills the panel; a click on the `<dialog>` itself closes it. The sidebar's collapsible sections are handled by the `sidebar-active` controller. **Menus** (e.g. the header user menu) use the kit's `<twig:DropdownMenu>`.
- Icons via Symfony UX Icons. Charts via Chart.js (Symfony UX).
- Prefer existing UI components, Twig components, and styling patterns where possible to maintain site-wide consistency.

## Testing

- Tests use `APP_ENV=test` + DAMA Doctrine Test Bundle (transaction rollback per test).
- Tests mirror `src/` structure in `tests/`.
- Factories in `tests/Shared/Factory/` (Zenstruck Foundry). Use `Factories` trait in test classes.
- Auth: `#[WithStory(StaffUserStory::class)]` or `UserFactory::new()->asStaff()->create()`. For delete handlers: `#[WithStory(SuperAdminUserStory::class)]` or `UserFactory::new()->asSuperAdmin()->create()`.
- Flow tests use `HasBrowser` trait (Zenstruck Browser). Named `{Feature}FlowTest.php` in `tests/{Context}/UI/`.
- A feature isn't done until a test exercises it the way a caller would — an HTTP request for a controller, a handler call for a handler — not just "it didn't throw".

## Code Style

- Readonly properties where possible; constructor property promotion.
- No Yoda conditions (`$value === null`, not `null === $value`).
- Full type hints on all methods.
- String concatenation: `'foo' . $bar` (space around `.`).
- Rich entities with domain logic; thin controllers (delegate to Flow/handlers).
- Use existing Form model / type / mapper patterns.
- Use repositories for all queries.

## Symfony Conventions

- **Attributes and autowiring, not config**: follow the surrounding code (`#[Route]`, `#[AsCommand]`, `#[AsEventListener]`, `#[Autowire(param:/env:)]`, `#[Target]`). A YAML service or route definition is the last resort.
- **Readonly services**: don't mark a service class `readonly` if it might become lazy — a lazy proxy can't extend a `readonly` class. DTOs, commands and value objects are fine.
- **New capabilities via Flex**: `composer require <package>` and let the recipe register the bundle and base config. Don't hand-edit `config/bundles.php` or hand-write a bundle's base config.
- **Use the framework before building or importing**: check for a Symfony component before hand-writing infrastructure (locks, caches, HTTP clients, schedulers) or adding a third-party library.
- **Makers**: pass every argument up front with `--no-interaction` where supported; makers prompt by default, which hangs a non-interactive shell. If a maker still needs input, hand-write the code.

### Discover, don't guess

Framework APIs change between versions. Look things up in the project rather than relying on memory:

- `symfony console debug:router`, `debug:container`, `debug:autowiring <name>`, `debug:config <bundle>`, `config:dump-reference <bundle>` — what exists and how it is configured.
- `symfony console lint:container`, `lint:twig templates/`, `lint:yaml config/` — validate before running.
- When something fails, read `var/log/dev.log` and the web profiler (`/_profiler`) before changing code.
- Read the installed source under `vendor/`, and use docs matching the version in `composer.json`.

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
