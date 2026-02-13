# SupplyMars Codebase Audit Report

**Date:** 2026-02-13
**Branch:** ai/next
**Review Panel:** PHP/Symfony architect, DDD specialist, UI/UX reviewer, test engineer, DevOps pragmatist

---

## A) Executive Summary

**SupplyMars** is a Mars-themed e-commerce and operations platform built on PHP 8.5+ / Symfony 8.0.x. It simulates a multi-supplier procurement pipeline: products are sourced from competing suppliers, orders split across them by cost and stock, pricing cascades through a three-level hierarchy, and console-command-driven simulation keeps the system alive with realistic data. The frontend is server-rendered with Twig, Turbo, and Stimulus -- no SPA framework.

### Top Strengths

1. **Exceptionally consistent architecture.** The FormFlow abstraction, CQRS command/handler pattern, and DDD layering are applied uniformly across all 10 bounded contexts. Controllers average 5-8 lines per method.
2. **Outstanding documentation.** 10 developer docs, 8 ADRs, 4 pattern specs, and a user manual. Docs-to-code alignment is high -- the codebase does what the docs say it does.
3. **Strong code quality tooling.** PHPStan Level 7 with **zero baseline suppressions**, Rector with comprehensive rulesets, 254 tests with 34 factories, and DAMA transaction rollback per test.

### Top Risks

1. **No database backup strategy.** Production MySQL data has no documented or automated backup mechanism. A single failure could cause total data loss.
2. **Silent failure modes in batch processing.** Console commands swallow exceptions in loops, meaning partial failures go unnoticed. Combined with no centralized logging/alerting, operational failures are invisible.
3. **Unused async event infrastructure.** `AsyncDomainEventInterface` and `MessageBus` dispatch logic exist but are dead code. All domain events are synchronous, meaning a slow listener blocks the HTTP request.

### If You Only Do 3 Things Next

1. **Implement automated database backups** with pre-deployment snapshots and daily offsite copies.
2. **Add try-catch with logging inside all console command loops** so batch processing is resilient and failures are observable.
3. **Decide on async events:** either implement `AsyncDomainEventInterface` on appropriate events (pricing cascade, audit logging) or remove the dead infrastructure to reduce confusion.

---

## B) Project Intent & Alignment

The documentation claims a modular monolith with strong DDD influences, simulation-first design, server-driven UI, and event-driven pricing cascades. The code delivers on all of these.

### Claims vs Reality

| Docs Claim | Code Reality | Verdict |
|---|---|---|
| Modular monolith with 10 bounded contexts | 10 contexts, each with DDD layers, clean namespace separation | Aligned |
| FormFlow pattern for all CRUD controllers | 18/24 controllers use FormFlow; 6 correctly exempt (auth, audit, reporting, static) | Aligned |
| ULID public IDs on all entities | All aggregates use `HasPublicUlid`; reporting read-models correctly omit it | Aligned |
| Event-driven pricing cascades | 6 pricing listeners handle category/subcategory/VAT/supplier events | Aligned |
| Simulation-first via console commands | 20 commands drive full order lifecycle with timing gates | Aligned |
| Server-driven UI, no SPA | Twig + Turbo + Stimulus; ~1,052 lines of JS across 18 controllers | Aligned |
| Two-layer reporting (daily + summaries) | Implemented with separate entity classes and scheduled recalculation | Aligned |
| Rich domain models, thin controllers | Controllers delegate to flows/handlers; entities contain business logic | Aligned |
| `bcmath` for financial precision | Used consistently in pricing, cost calculations, and VAT | Aligned |
| Async events via RabbitMQ | **Partially misaligned:** `AsyncDomainEventInterface` defined but never implemented on any event. RabbitMQ used for Messenger transport but not domain events. | Gap |

### Documentation Gaps

- **InlineEdit pattern** is documented in `Docs/patterns/InlineEdit/README.md` but not referenced from the main `Docs/README.md` index.
- No **troubleshooting guide** for common development issues (the setup doc covers some failures but is incomplete).
- **ADR for the Note/Ticket context** is missing -- it was added without a corresponding architectural decision record.

---

## C) Architecture & Boundaries

### Module Structure

The 10 bounded contexts are cleanly separated in `src/`:

```
Audit (7 tests)     Catalog (46 tests)    Customer (11 tests)
Home (1 test)       Note (13 tests)       Order (23 tests)
Pricing (20 tests)  Purchasing (57 tests)  Reporting (27 tests)
Review (18 tests)   Shared (31 tests)
```

Each follows the prescribed DDD layers: `Application/`, `Domain/`, `Infrastructure/`, `UI/`. The `Shared/` kernel provides cross-cutting concerns (FormFlow, Result, FlusherInterface, events, Twig components).

### Coupling Analysis

Cross-context coupling is intentional and minimal:

| From | To | Mechanism | Assessment |
|---|---|---|---|
| Pricing | Catalog, Purchasing | Event listeners (6 listeners) | Correct: pricing reacts to upstream changes |
| Audit | Order, Purchasing | Event listeners (2 listeners) | Correct: audit is a logging concern |
| Review | Catalog | Entity reference (Product) | Acceptable: reviews belong to products |
| Reporting | Catalog, Purchasing, Order | Entity references in read-models | Acceptable: reporting aggregates cross-context data |
| Note | Customer | Entity reference (User) | Acceptable: tickets belong to users |

**No circular dependencies detected.** Cross-context communication uses domain events (sync) for state-changing reactions and direct entity references only for read-side relationships.

### Data Access Patterns

- **Repository interfaces** in `Domain/Repository/` with Doctrine implementations in `Infrastructure/Persistence/`. Every entity has both. Consistent.
- **FlusherInterface** wraps Doctrine flush with change detection. Returns `bool` indicating if changes were persisted. Used consistently in handlers -- **except** reporting handlers, which bypass it with direct `EntityManager::flush()`.
- **Doctrine configuration** maps all 10 contexts in `config/packages/doctrine.yaml` with attribute-based mapping. Production uses Redis-backed query and result cache.

### Event Architecture

- **21 concrete domain events** across 5 contexts.
- **DomainEventDispatcher** (Doctrine postFlush listener) dispatches events after persistence.
- **StatusWasChangedEventInterface** provides a consistent contract for status-change events.
- **Dead code:** `AsyncDomainEventInterface` and its `MessageBus` dispatch path in `DomainEventDispatcher` lines 56-60 are never exercised.

---

## D) Code Quality

### Conventions & Consistency

| Convention | Compliance | Evidence |
|---|---|---|
| `readonly` properties/classes | 95%+ | All commands, handlers, DTOs, value objects. Exception: `ReviewGenerator` |
| Constructor property promotion | 100% | Universal across entities and services |
| No Yoda conditions | 100% | Enforced by PHP-CS-Fixer |
| Full type hints | 100% | PHPStan Level 7 with zero baseline |
| Route naming `app_{context}_{entity}_{action}` | 100% | All 24 controllers |
| `#[ValueResolver('public_id')]` for entity resolution | 100% | All CRUD controllers |

### Static Analysis

- **PHPStan Level 7** with **zero baseline suppressions**. This is exceptional -- it means every line of code passes strict type analysis without exceptions.
- **Rector** configured with 8 prepared sets including dead code, code quality, type declarations, and Doctrine quality.
- **PHP-CS-Fixer** with `@Symfony` rules and explicit `yoda_style: false`.

### Error Handling

**Handlers:** Consistently return `Result::ok()` or `Result::fail()`. Business logic failures are caught and converted to Result failures. No exceptions leak to controllers.

**Console commands:** This is the weakest area. Most command loops have no try-catch, meaning a single handler failure stops the entire batch. Example from `OrderAllocator`:

```
src/Purchasing/Application/Service/OrderAllocator.php:45-47
try { ... } catch (\Throwable) { continue; }
```

This silently swallows all exceptions without logging. While the service verifies allocation success post-hoc, failures are invisible to operators.

### Typing & Precision

- **Financial calculations** use `bcmath` consistently (`numeric-string` types, `bcmul`, `bcdiv`, `bcadd`).
- **6 price models** (NONE, DEFAULT, PRETTY_00, PRETTY_10, PRETTY_49, PRETTY_95, PRETTY_99) implemented as an enum with `getPrettyPrice()` method.
- **Decimal columns** use `precision: 10, scale: 2` throughout.

### Security

- **CSRF protection** enabled on all forms and login.
- **Password hashing** uses `auto` algorithm (bcrypt with cost=4 in tests).
- **SQL injection** prevented by Doctrine parameter binding.
- **XSS** mitigated by Twig auto-escaping.
- **Remember-me** is `always_remember_me: true` -- less secure, as users never need to re-authenticate.
- **No login throttling** -- `login_throttling: null` in security config.
- **No rate limiting** at any layer.

---

## E) UI/UX & Front-End

### Architecture

The frontend follows the server-driven UI pattern documented in ADR-008. State lives in HTML, not JavaScript.

**Technology stack:**
- Twig templates + Twig Components (compile-time)
- Symfony Forms with Flowbite form theme
- Stimulus.js for progressive enhancement (~18 controllers, ~1,052 lines)
- Turbo for navigation and Turbo Streams for dynamic updates
- Tailwind CSS (utility-first)
- Symfony Asset Mapper (zero-build pipeline)

### Template Hierarchy

```
base.html.twig
  layouts/admin.html.twig       # Staff/admin layout with sidebar navigation
  layouts/public.html.twig      # Public-facing layout
  layouts/auth.html.twig        # Authentication pages
  modal_base.html.twig          # Minimal layout for modal content
```

Frame hierarchy: `body` (main content, URL updates), `modal` (overlay, no URL change), `{model}-table` (search results), `reports` (dashboards).

### Component Reuse

**Twig Components** in `src/Shared/UI/Twig/Components/` and `templates/components/`:
- `Badge`, `StatusBadge`, `StatusBadgeGroup` -- status display
- `DataTable`, `DataTableRow` -- tabular data
- `Button`, `IconButton`, `ButtonGroup` -- actions
- `InlineEdit` -- inline editing pattern
- `FlashMessage`, `Toast` -- notifications
- `Modal`, `Confirm` -- dialogs
- `Pagination`, `SearchBar` -- navigation

Components are well-structured and reused across contexts. The `InlineEdit` component is particularly sophisticated with auto-grow inputs, keyboard shortcuts, and preload-on-hover.

### Stimulus Controllers

| Controller | Purpose | Lines |
|---|---|---|
| `basic_modal_controller` | Native `<dialog>` management | ~60 |
| `inline_edit_controller` | Click-to-edit with auto-grow | ~180 |
| `dropzone_controller` | Image upload with Dropzone.js | ~55 |
| `datepicker_controller` | Flowbite datepicker integration | ~26 |
| `line_chart_controller` | Chart.js line chart configuration | ~60 |
| `doughnut_chart_controller` | Chart.js doughnut with click-through | ~80 |
| `sortable_controller` | Drag-and-drop reordering | ~40 |
| `flash_controller` | Auto-dismiss flash messages | ~20 |
| Others (10+) | Various UI enhancements | ~530 |

All controllers use `stimulusFetch: 'lazy'` for code-splitting. Clean connect/disconnect lifecycle with proper cleanup.

### Accessibility

- Native `<dialog>` element for modals (better than custom implementations).
- `aria-hidden="true"` on decorative elements (e.g., InlineEdit sizer span).
- Semantic HTML structure with `<main>`, `<nav>`, `<header>`.
- Keyboard support in InlineEdit (Enter to save, Escape to cancel).
- **Gap:** No explicit `aria-label` on icon-only buttons. No skip-navigation link. No focus-trap in modals (though native `<dialog>` provides some focus management).

### Dark Mode

Tailwind dark mode classes (`dark:`) applied consistently throughout templates. Both admin and public layouts support dark mode.

---

## F) Testing

### Test Pyramid

```
254 Total Tests

         /---------\
        / Domain:28 \        (11%) Pure unit tests - entities, VOs, enums
       /  (11%)      \
      /----------------\
     / Application:92   \    (36%) Handler tests, service tests, listener tests
    /    (36%)           \
   /----------------------\
  / UI/Flow:134            \  (53%) Browser tests (HasBrowser), console command tests
 /   (53%)                  \
/----------------------------\
```

The pyramid is inverted from the traditional ideal (more integration than unit tests), but this is **appropriate for a form-heavy CRUD application** where business value concentrates at the integration boundary.

### Factory Quality: Excellent

**34 factories** in `tests/Shared/Factory/` with:
- Service-based instantiation (`MarkupCalculator`, `UserPasswordHasher` injected)
- Named constructors via `Instantiator::namedConstructor()`
- `LazyValue::memoize()` for relationship initialization
- Builder methods: `ProductFactory::withActiveSource()`, `UserFactory::asStaff()`, `SupplierFactory::asWarehouse()`
- `afterInstantiate()` hooks for complex setup

### Coverage of Core Business Logic

| ADR/Domain Area | Test Coverage | Key Test Files |
|---|---|---|
| ADR-001: Multi-supplier sourcing | Strong | `OrderItemAllocatorTest`, `OrderAllocatorTest` |
| ADR-002: Order line splitting | Strong | `PurchaseOrderStatusConsistencyTest` |
| ADR-003: Simulation lifecycle | Strong | 15 console command tests |
| ADR-004: Pricing cascades | Excellent | `MarkupCalculatorDomainTest` (17 tests with DataProvider) |
| ADR-005: Two-layer reporting | Good | 10+ handler tests |
| ADR-006: FormFlow | Strong | Dedicated `InlineEditFlowTest` + per-context flow tests |

### Coverage Gaps

1. **Home context:** 1 test (homepage only).
2. **Customer context:** 11 tests -- registration flow, password reset, and address management are undertested.
3. **Parameterized tests:** Only 5 `@dataProvider` methods. Status transition combinations, price model edge cases, and shipping method variants would benefit from parameterization.
4. **Negative paths in console commands:** Few tests for partial failures, network errors, or invalid data scenarios.
5. **No mutation testing** (Infection/Pest) to validate assertion quality.

### Code Quality Tooling

| Tool | Configuration | Assessment |
|---|---|---|
| PHPStan | Level 7, Symfony + Doctrine extensions | Excellent -- zero baseline |
| PHP-CS-Fixer | @Symfony, yoda_style: false | Clean |
| Rector | 8 prepared sets | Comprehensive |
| PHPUnit | failOnDeprecation/Notice/Warning: true | Strict |
| DAMA Doctrine Test Bundle | Transaction rollback per test | Proper isolation |

---

## G) DevOps / Production Readiness

### Docker Architecture

**Multi-stage Dockerfile** (4 stages: `php-base` -> `php-dev`/`php-prod-builder`/`cron-prod`/`nginx-dev`/`nginx-prod`):
- Production optimizations: classmap-authoritative autoloader, asset precompilation, dev/test file cleanup.
- Non-root execution (www-data) for cache building.
- OPcache enabled, but **preload disabled** (commented out in `docker/php/conf.d/20-app.prod.ini`).

**7 services** in Docker Compose with comprehensive healthchecks:

| Service | Image | Healthcheck | Assessment |
|---|---|---|---|
| MySQL 8.4 | Persistent volume | `mysqladmin ping` | Good |
| Redis 8.4 | Persistent volume | `redis-cli ping` | Good |
| RabbitMQ 4.2 | Management UI | `diagnostics ping` | Good |
| PHP-FPM | App container | `php-fpm -t` | Good |
| Messenger worker | App container | `ps grep messenger` | Fragile |
| Cron | App container | `ps grep cron` | Fragile |
| Nginx | Reverse proxy | `curl -kf https://localhost` | Good |

### CI/CD Pipeline

**GitHub Actions** (`ci.yml`):
1. **On PRs + main pushes:** PHP-CS-Fixer dry-run, PHPStan, Doctrine schema validation, full test suite.
2. **On main push only:** SSH deploy to AWS Lightsail -- `git reset --hard`, Docker Compose up with `--wait`.

**Concerns:**
- No staging environment or pre-deployment smoke tests.
- No rollback strategy beyond reverting git.
- No database backup before deployment.
- Hardcoded deploy path (`/home/ubuntu/supplymars`).

### Production Gaps

| Gap | Risk Level | Impact |
|---|---|---|
| No database backups | **Critical** | Total data loss on failure |
| No APM/monitoring | **High** | Blind to performance degradation |
| No centralized logging | **High** | Logs lost on container restart |
| No rate limiting | **Medium** | Vulnerable to abuse |
| OPcache preload disabled | **Low** | ~10-15% performance opportunity |
| Single Redis instance | **Low** | Sessions, cache, and queues compete for memory |
| No login throttling | **Medium** | Brute-force vulnerability |
| `always_remember_me: true` | **Low** | Users never forced to re-authenticate |

### Cron Schedule

Production cron (`docker/php/cron/prod-crontab`) runs simulation commands on a schedule:

```
*/5  min  create-customer-orders (0-2)       Keeps orders flowing
*/15 min  accept-purchase-orders (20)        Simulates supplier responses
*/30 min  build-purchase-orders (20)         Allocates to suppliers
1h        ship/deliver-purchase-order-items   Progresses PO lifecycle
*/15 min  update-supplier-stock (20)         Fluctuates inventory
Nightly + hourly  reporting calculations     Near-real-time dashboards
```

**Issue:** Build POs runs every 30 min but Accept POs runs every 15 min. POs could be accepted before they're built. The build step should run more frequently or be synchronized.

---

## H) Documentation Quality

### Strengths

- **Comprehensive coverage:** 10 developer docs + 8 ADRs + 4 pattern specs + CLI reference + user manual.
- **ADRs document "why":** Each explains context, decision, consequences (positive and negative). This is rare and valuable.
- **Pattern specs are actionable:** FormFlow, Turbo, UI, and InlineEdit specs include file locations, API references, and code examples.
- **CLI reference is complete:** All 20 commands documented with arguments, options, and usage.
- **Code reference provides a navigation map:** `10-code-reference.md` maps key entry points by use case.

### Gaps

1. **No troubleshooting guide** for common development issues beyond setup failures.
2. **InlineEdit pattern not indexed** in the main `Docs/README.md`.
3. **No ADR for the Note/Ticket context** -- it was added without documenting the architectural decision.
4. **No runbook** for production operations (incident response, scaling, maintenance).
5. **Operational doc (06-operations.md)** discusses cron but doesn't cover monitoring, alerting, or backup procedures.

### Highest-Leverage Improvement

Add a **production runbook** covering: backup/restore procedures, incident response, scaling guidance, common operational tasks, and monitoring setup. This would address the biggest operational blind spot.

---

## I) Recommendations

### 1. Implement Automated Database Backups

| | |
|---|---|
| **Area** | DevOps |
| **Problem** | No backup strategy exists. Production MySQL data is at risk of total loss from hardware failure, accidental deletion, or failed deployment. |
| **Proposed Change** | Add `mysqldump` cron job (daily at 02:00) writing to S3 with 30-day retention. Add pre-deployment backup in CI/CD pipeline. |
| **Importance** | **H** |
| **Effort** | S |
| **Risk** | L |
| **Payoff** | Immediate |
| **Evidence** | `compose.yaml` (no backup volume), `.github/workflows/ci.yml` (no backup step), `docker/php/cron/prod-crontab` (no backup job) |

### 2. Add Error Handling in Console Command Loops

| | |
|---|---|
| **Area** | Application / Console |
| **Problem** | Console commands iterate entities without try-catch. A single handler failure stops the entire batch, and with no logging, the failure is invisible. |
| **Proposed Change** | Wrap each iteration in try-catch, log failures with entity ID, continue processing. Add a summary of failures at the end. |
| **Importance** | **H** |
| **Effort** | S |
| **Risk** | L |
| **Payoff** | Immediate |
| **Evidence** | `src/Purchasing/UI/Console/BuildPOsCommand.php`, `AcceptPOsCommand.php`, `ShipPOItemsCommand.php`, `DeliverPOItemsCommand.php`, `RefundPOsCommand.php` |

### 3. Resolve AsyncDomainEventInterface Dead Code

| | |
|---|---|
| **Area** | Architecture |
| **Problem** | `AsyncDomainEventInterface` and its `MessageBus` dispatch path in `DomainEventDispatcher` are defined but never used. This creates confusion about the intended event architecture. |
| **Proposed Change** | Either: (a) implement async dispatch on appropriate events (audit logging, pricing cascade for bulk changes), or (b) remove the interface and dead dispatch code. |
| **Importance** | **H** |
| **Effort** | M |
| **Risk** | M |
| **Payoff** | Soon |
| **Evidence** | `src/Shared/Domain/Event/AsyncDomainEventInterface.php`, `src/Shared/Infrastructure/Persistence/Doctrine/EventListener/DomainEventDispatcher.php:56-60` |

### 4. Use FlusherInterface in Reporting Handlers

| | |
|---|---|
| **Area** | Code Quality |
| **Problem** | Reporting handlers (`CalculateOrderSalesHandler`, `CalculateCustomerSalesHandler`, `CalculateProductSalesHandler`) bypass `FlusherInterface` and use `EntityManager::persist()`/`flush()` directly. This breaks the established pattern and bypasses change detection. |
| **Proposed Change** | Inject `FlusherInterface` and repository, use `$repository->add()` + `$flusher->flush()`. |
| **Importance** | **M** |
| **Effort** | S |
| **Risk** | L |
| **Payoff** | Immediate |
| **Evidence** | `src/Reporting/Application/Handler/CalculateOrderSalesHandler.php`, `CalculateCustomerSalesHandler.php`, `CalculateProductSalesHandler.php` |

### 5. Add Login Throttling and Rate Limiting

| | |
|---|---|
| **Area** | Security |
| **Problem** | Login throttling is explicitly disabled (`login_throttling: null`). No rate limiting at any layer. Brute-force attacks against the login form are unmitigated. |
| **Proposed Change** | Enable Symfony's built-in `login_throttling` (e.g., `max_attempts: 5`). Add Nginx rate limiting for `/login` and form submission endpoints. |
| **Importance** | **M** |
| **Effort** | S |
| **Risk** | L |
| **Payoff** | Immediate |
| **Evidence** | `config/packages/security.yaml` (login_throttling: null), `docker/nginx/conf.d/prod.conf` (no rate limiting) |

### 6. Add Centralized Logging

| | |
|---|---|
| **Area** | DevOps |
| **Problem** | Logs go to container stderr (Docker) with no aggregation. Container restarts lose all logs. No alerting on errors. |
| **Proposed Change** | Add CloudWatch Logs agent or similar (Loki, ELK) to collect and retain logs. Set up alerts for error-level log spikes. |
| **Importance** | **M** |
| **Effort** | M |
| **Risk** | L |
| **Payoff** | Soon |
| **Evidence** | `config/packages/monolog.yaml` (prod: php://stderr), `compose.yaml` (no logging driver) |

### 7. Fix Cron Schedule: Build Before Accept

| | |
|---|---|
| **Area** | Operations |
| **Problem** | `accept-purchase-orders` runs every 15 min but `build-purchase-orders` runs every 30 min. POs can be accepted before they're built, creating inconsistent states. |
| **Proposed Change** | Run `build-purchase-orders` every 15 min (before accept), or chain them sequentially. |
| **Importance** | **M** |
| **Effort** | S |
| **Risk** | L |
| **Payoff** | Immediate |
| **Evidence** | `docker/php/cron/prod-crontab` lines 6-8 |

### 8. Replace Command Chaining Antipattern

| | |
|---|---|
| **Area** | Code Quality |
| **Problem** | Reporting commands chain by directly calling `$this->otherCommand->__invoke()`. This is fragile and couples commands tightly. |
| **Proposed Change** | Use Symfony's `Application::find()` + `CommandTester::run()` or extract shared logic into a service. |
| **Importance** | **M** |
| **Effort** | S |
| **Risk** | L |
| **Payoff** | Soon |
| **Evidence** | `src/Reporting/UI/Console/CalculateCustomerSalesCommand.php`, `CalculateProductSalesCommand.php`, `CalculateOrderSalesCommand.php` |

### 9. Enrich Thin Event Payloads

| | |
|---|---|
| **Area** | Architecture |
| **Problem** | Some events carry only an entity ID (`OrderWasCreatedEvent`, `ReviewWasCreatedEvent`, `OrderItemWasCreatedEvent`). Listeners must re-fetch from the database, adding unnecessary queries. |
| **Proposed Change** | Include key attributes in event payload (e.g., product ID, customer ID, total) so listeners can act without re-fetching. |
| **Importance** | **L** |
| **Effort** | S |
| **Risk** | L |
| **Payoff** | Soon |
| **Evidence** | `src/Order/Domain/Model/Order/Event/OrderWasCreatedEvent.php`, `src/Review/Domain/Model/Review/Event/ReviewWasCreatedEvent.php` |

### 10. Increase Parameterized Tests

| | |
|---|---|
| **Area** | Testing |
| **Problem** | Only 5 `@dataProvider` methods across 254 tests. Status transition combinations, price model edge cases, and form validation scenarios are tested with repetitive individual methods. |
| **Proposed Change** | Add DataProviders for: status transition matrices, price model rounding, form validation rules. Target: 15+ DataProviders. |
| **Importance** | **L** |
| **Effort** | M |
| **Risk** | L |
| **Payoff** | Long-term |
| **Evidence** | `tests/Shared/Domain/Service/Pricing/MarkupCalculatorDomainTest.php` (good example), `tests/Order/Domain/OrderDomainTest.php` (27 individual test methods that could be parameterized) |

### 11. Enable OPcache Preload

| | |
|---|---|
| **Area** | DevOps |
| **Problem** | OPcache preload is commented out in production PHP config. This means every request incurs class loading overhead. |
| **Proposed Change** | Uncomment and configure `opcache.preload` in `docker/php/conf.d/20-app.prod.ini`. Also set `opcache.validate_timestamps=0` for production. |
| **Importance** | **L** |
| **Effort** | S |
| **Risk** | L |
| **Payoff** | Immediate |
| **Evidence** | `docker/php/conf.d/20-app.prod.ini` lines 1-3 (commented out) |

### 12. Add Production Runbook

| | |
|---|---|
| **Area** | Documentation |
| **Problem** | No operational runbook exists. Production incidents, scaling, backup/restore, and maintenance procedures are undocumented. |
| **Proposed Change** | Create `Docs/11-runbook.md` covering: incident response, backup/restore, scaling, deploy rollback, monitoring setup, common operational tasks. |
| **Importance** | **M** |
| **Effort** | M |
| **Risk** | L |
| **Payoff** | Soon |
| **Evidence** | `Docs/06-operations.md` (covers cron but not incident response or backups) |

### 13. Add ADR for Note/Ticket Context

| | |
|---|---|
| **Area** | Documentation |
| **Problem** | The Note context (support pools, tickets, messages) was added without an ADR documenting the design decisions. |
| **Proposed Change** | Write `Docs/adr/009-support-ticket-system.md` documenting: context, decision, pool/ticket/message model, visibility rules, and consequences. |
| **Importance** | **L** |
| **Effort** | S |
| **Risk** | L |
| **Payoff** | Long-term |
| **Evidence** | `src/Note/` exists, `Docs/adr/` has ADRs 001-008 but nothing for Note |

### 14. Add Accessibility Improvements

| | |
|---|---|
| **Area** | UI/UX |
| **Problem** | Icon-only buttons lack `aria-label`. No skip-navigation link. Focus management in modals relies solely on native `<dialog>` behavior. |
| **Proposed Change** | Add `aria-label` to all icon-only buttons. Add skip-nav link to admin layout. Audit modal focus trapping with screen reader. |
| **Importance** | **L** |
| **Effort** | S |
| **Risk** | L |
| **Payoff** | Long-term |
| **Evidence** | `templates/shared/form_flow/inline_edit_form.html.twig` (icon buttons have `title` but not `aria-label`), `templates/layouts/admin.html.twig` (no skip-nav) |

### 15. Expand Customer Context Test Coverage

| | |
|---|---|
| **Area** | Testing |
| **Problem** | Customer context has only 11 tests. Registration flow, password reset, email verification, and address management are undertested relative to their user-facing importance. |
| **Proposed Change** | Add tests for: registration validation, email verification happy/error paths, password reset token expiry, address CRUD, profile update. Target: 25+ tests. |
| **Importance** | **L** |
| **Effort** | M |
| **Risk** | L |
| **Payoff** | Long-term |
| **Evidence** | `tests/Customer/` (11 tests vs. Purchasing's 57) |

---

## J) Roadmap

### Phase 0: Quick Wins (1-3 days)

| PR | Description | Recommendations |
|---|---|---|
| **PR-1: Database backup cron** | Add `mysqldump` to cron with S3 upload. Add pre-deploy backup to CI. | #1 |
| **PR-2: Console command resilience** | Add try-catch + logging in all command loops. | #2 |
| **PR-3: Login throttling** | Enable `login_throttling: max_attempts: 5` in security.yaml. | #5 |
| **PR-4: Fix cron schedule** | Sync build/accept PO timing. | #7 |
| **PR-5: Enable OPcache preload** | Uncomment in prod PHP config, add `validate_timestamps=0`. | #11 |

### Phase 1: High-Leverage Refactors (1-2 weeks)

| PR | Description | Recommendations |
|---|---|---|
| **PR-6: Async event decision** | Either implement `AsyncDomainEventInterface` on audit/pricing events, or remove dead code. | #3 |
| **PR-7: Reporting handler consistency** | Refactor reporting handlers to use `FlusherInterface`. | #4 |
| **PR-8: Command chaining refactor** | Replace `__invoke()` chaining with proper Symfony command invocation or shared services. | #8 |
| **PR-9: Centralized logging** | Set up log aggregation (CloudWatch/Loki). Configure alerts. | #6 |
| **PR-10: Production runbook** | Write `Docs/11-runbook.md` with operational procedures. | #12 |

### Phase 2: Long-Term Quality (2-4 weeks)

| PR | Description | Recommendations |
|---|---|---|
| **PR-11: Enrich event payloads** | Add key attributes to thin domain events. | #9 |
| **PR-12: Parameterized tests** | Add DataProviders for status transitions, price models, and validation rules. | #10 |
| **PR-13: Customer test coverage** | Expand Customer context to 25+ tests. | #15 |
| **PR-14: Accessibility audit** | Add aria-labels, skip-nav, and focus management. | #14 |
| **PR-15: Note context ADR** | Document architectural decisions for the ticket system. | #13 |

---

## K) Questions

None blocking. All findings are based on concrete code evidence and documented patterns.
