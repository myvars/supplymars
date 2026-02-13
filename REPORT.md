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

1. ~~**No database backup strategy.** Production MySQL data has no documented or automated backup mechanism. A single failure could cause total data loss.~~ **Done.** `app:backup-database` (daily cron to S3) and `app:restore-database` (dev-only) implemented.
2. ~~**Silent failure modes in batch processing.** Console commands swallow exceptions in loops, meaning partial failures go unnoticed. Combined with no centralized logging/alerting, operational failures are invisible.~~ **Done.** All command loops now have try-catch with structured logging, failure counters, warning output, and `Command::FAILURE` exit codes.
3. ~~**Unused async event infrastructure.** `AsyncDomainEventInterface` and `MessageBus` dispatch logic exist but are dead code. All domain events are synchronous, meaning a slow listener blocks the HTTP request.~~ **Done.** Decision: keep the infrastructure for future use. Not dead code — intentional forward investment.

### If You Only Do 3 Things Next

1. ~~**Implement automated database backups** with pre-deployment snapshots and daily offsite copies.~~ **Done.**
2. ~~**Add try-catch with logging inside all console command loops** so batch processing is resilient and failures are observable.~~ **Done.**
3. ~~**Decide on async events:** either implement `AsyncDomainEventInterface` on appropriate events (pricing cascade, audit logging) or remove the dead infrastructure to reduce confusion.~~ **Done.** Decision: keep for future use.

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
| Async events via RabbitMQ | `AsyncDomainEventInterface` defined and dispatch path wired. No events use it yet — intentionally retained for future use. RabbitMQ active for Messenger transport. | Aligned (planned) |

### Documentation Gaps

- **InlineEdit pattern** is documented in `Docs/patterns/InlineEdit/README.md` but not referenced from the main `Docs/README.md` index.
- No **troubleshooting guide** for common development issues (the setup doc covers some failures but is incomplete).
- ~~**ADR for the Note/Ticket context** is missing -- it was added without a corresponding architectural decision record.~~ **Done.** `Docs/adr/009-support-ticket-system.md` created.

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
- **FlusherInterface** wraps Doctrine flush with change detection. Returns `bool` indicating if changes were persisted. Used consistently in all handlers.
- **Doctrine configuration** maps all 10 contexts in `config/packages/doctrine.yaml` with attribute-based mapping. Production uses Redis-backed query and result cache.

### Event Architecture

- **21 concrete domain events** across 5 contexts.
- **DomainEventDispatcher** (Doctrine postFlush listener) dispatches events after persistence.
- **StatusWasChangedEventInterface** provides a consistent contract for status-change events.
- **Planned infrastructure:** `AsyncDomainEventInterface` and its `MessageBus` dispatch path in `DomainEventDispatcher` are wired but not yet used by any event. Retained for future use.

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

**Console commands:** ~~This is the weakest area. Most command loops have no try-catch, meaning a single handler failure stops the entire batch.~~ **Resolved.** All command loops now wrap each iteration in try-catch, log failures with structured context (entity ID, error message), continue processing, and return `Command::FAILURE` if any items failed. `OrderAllocator`'s previously silent catch now logs a warning. `CreateCustomerOrdersCommand` additionally clears the EntityManager after failures to reset UoW state. Documentation updated in `Docs/06-operations.md` and `Docs/09-cli-reference.md`.

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
- ~~**No login throttling**~~ -- **Resolved.** `login_throttling` enabled (5 attempts/15 min). Nginx rate limiting on `/login`, `/register`, `/reset-password`.

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
- ~~**Gap:** No explicit `aria-label` on icon-only buttons. No skip-navigation link. No focus-trap in modals (though native `<dialog>` provides some focus management).~~ **Resolved.** `aria-label` added to all icon-only buttons/links. Skip-navigation link added to `base.html.twig`. Native `<dialog>` focus management confirmed adequate.

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
| ~~No database backups~~ | ~~**Critical**~~ | ~~Total data loss on failure~~ **Resolved** |
| No APM/monitoring | **High** | Blind to performance degradation |
| No centralized logging | **High** | Logs lost on container restart |
| ~~No rate limiting~~ | ~~**Medium**~~ | ~~Vulnerable to abuse~~ **Resolved** |
| OPcache preload disabled | **Low** | ~10% performance opportunity (deferred — PHP 8.5 compatibility issue) |
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

~~**Issue:** Build POs runs every 30 min but Accept POs runs every 15 min. POs could be accepted before they're built. The build step should run more frequently or be synchronized.~~ **Resolved.** Build now runs every 15 min, accept offset by 1 min.

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
4. ~~**No runbook** for production operations (incident response, scaling, maintenance).~~ **Done.** `Docs/11-runbook.md` created.
5. **Operational doc (06-operations.md)** discusses cron but doesn't cover monitoring, alerting, or backup procedures.

### Highest-Leverage Improvement

Add a **production runbook** covering: backup/restore procedures, incident response, scaling guidance, common operational tasks, and monitoring setup. This would address the biggest operational blind spot.

---

## I) Recommendations

### ~~1. Implement Automated Database Backups~~ Done

| | |
|---|---|
| **Status** | **Complete** |
| **Resolution** | `app:backup-database` command with daily cron to S3 (30-day retention). `app:restore-database` (dev-only, `#[When(env: 'dev')]`) supports local and S3 restore. |

### ~~2. Add Error Handling in Console Command Loops~~ Done

| | |
|---|---|
| **Status** | **Complete** |
| **Resolution** | All 11 simulation/reporting commands and 2 service-layer loops (`OrderAllocator`, `ReviewGenerator`) now have try-catch with structured logging, `$failed` counters, warning output, and `Command::FAILURE` exit codes. `CreateCustomerOrdersCommand` additionally clears the EntityManager on failure. Docs updated in `06-operations.md` and `09-cli-reference.md`. |

### ~~3. Resolve AsyncDomainEventInterface Dead Code~~ Done

| | |
|---|---|
| **Status** | **Complete** |
| **Resolution** | Decision: keep `AsyncDomainEventInterface` and the `MessageBus` dispatch path as intentional forward infrastructure. Events will be migrated to async as needed (audit logging, pricing cascade for bulk changes). |

### ~~4. Use FlusherInterface in Reporting Handlers~~ Done

| | |
|---|---|
| **Status** | **Complete** |
| **Resolution** | All 6 reporting calculation handlers refactored to use `FlusherInterface` + typed repository injection. 9 repository interfaces extended with `add()`, `deleteBy*()`, and query methods. 9 Doctrine implementations updated with `add()` methods. Own-context repos injected via domain interface; cross-context repos via concrete Doctrine class (matching established pattern). PHPStan Level 7 clean, all 157 reporting tests pass. |

### ~~5. Add Login Throttling and Rate Limiting~~ Done

| | |
|---|---|
| **Status** | **Complete** |
| **Resolution** | Symfony `login_throttling` enabled (5 attempts/15 min per IP+username). Nginx rate limiting added for `/login` (10 req/min), `/register` (2 req/min), and `/reset-password/*` (2 req/min). Security docs updated. |

### ~~6. Add Centralized Logging~~ Deferred

| | |
|---|---|
| **Status** | **Deferred** |
| **Resolution** | Infrastructure task requiring external service setup (CloudWatch/Loki/ELK). Deferred for future implementation when monitoring stack is prioritized. |

### ~~7. Fix Cron Schedule: Build Before Accept~~ Done

| | |
|---|---|
| **Status** | **Complete** |
| **Resolution** | `build-purchase-orders` increased to every 15 min. `accept-purchase-orders` offset to :01,:16,:31,:46 (1 min after build). Ensures POs are always built before acceptance. Docs updated in `06-operations.md`. |

### ~~8. Replace Command Chaining Antipattern~~ Won't Fix

| | |
|---|---|
| **Status** | **Won't Fix** |
| **Resolution** | Decision: keep the current `__invoke()` chaining. The pattern works, cron already coordinates the commands independently, and the alternatives either lose the full command output (progress bars, verbose listings) or require injecting `Application` for no practical benefit. The coupling is acceptable given these commands are in the same context and always logically paired. |

### ~~9. Enrich Thin Event Payloads~~ Won't Fix

| | |
|---|---|
| **Status** | **Won't Fix** |
| **Resolution** | Events are intentionally thin per DDD convention. Pricing listeners need full entity traversal (e.g., iterating all products under a category) regardless of payload richness — no reasonable payload would eliminate those fetches. Creation events without listeners are retained for future use. |

### ~~10. Increase Parameterized Tests~~ Done

| | |
|---|---|
| **Status** | **Complete** |
| **Resolution** | Refactored `ReviewStatusTransitionTest` to use DataProviders for transition matrix (15 cases) and allowEdit rules (4 cases), replacing 18 individual methods. Remaining test files already use DataProviders where appropriate (MarkupCalculator, PriceModel) or test meaningfully distinct scenarios that don't benefit from parameterization. |

### ~~11. Enable OPcache Preload~~ Deferred

| | |
|---|---|
| **Status** | **Deferred** |
| **Resolution** | Preloading was intentionally disabled after the PHP 8.5 upgrade due to critical errors caused by changed behaviour. Symfony still supports preloading (~9-10% gain per benchmarks), but re-enabling requires investigating the PHP 8.5-specific failures first. Not a quick win. |

### ~~12. Add Production Runbook~~ Done

| | |
|---|---|
| **Status** | **Complete** |
| **Resolution** | Created `Docs/11-runbook.md` covering: service architecture reference, deployment (automated + manual + rollback), health checks, backup/restore procedures, maintenance mode, troubleshooting (502/504, queue backlog, cron, slow pages, failed messages, disk space), certificate renewal, and database maintenance. Cross-references `06-operations.md` and `09-cli-reference.md`. Added to `Docs/README.md` index. |

### ~~13. Add ADR for Note/Ticket Context~~ Done

| | |
|---|---|
| **Status** | **Complete** |
| **Resolution** | Created `Docs/adr/009-support-ticket-system.md` documenting: pool-based routing, staff subscription model, three-entity model (Pool → Ticket → Message), automatic status transitions by author type, snooze as orthogonal to status, denormalized listing fields, message visibility (PUBLIC/INTERNAL), and system messages for audit trail. Added to `Docs/README.md` index. |

### ~~14. Add Accessibility Improvements~~ Done

| | |
|---|---|
| **Status** | **Complete** |
| **Resolution** | Added `aria-label` to all icon-only buttons/links across 5 templates (inline edit save/cancel, ticket snooze/close/reopen, message delete, Card edit link, product image delete). Added skip-navigation link to `base.html.twig` with `id="main-content"` on `<main>`. Modal focus management confirmed adequate via native `<dialog>` + `showModal()`. |

### ~~15. Expand Customer Context Test Coverage~~ Deferred

| | |
|---|---|
| **Status** | **Deferred** |
| **Resolution** | Registration, email verification, and password reset currently rely on SymfonyCasts bundles. These will be rewritten to align with standard project patterns first. Test coverage expansion deferred until after that rewrite. |

---

## J) Roadmap

### Phase 0: Quick Wins (1-3 days)

| PR | Description | Recommendations |
|---|---|---|
| ~~**PR-1: Database backup cron**~~ | ~~Add `mysqldump` to cron with S3 upload. Add pre-deploy backup to CI.~~ **Done.** | #1 |
| ~~**PR-2: Console command resilience**~~ | ~~Add try-catch + logging in all command loops.~~ **Done.** | #2 |
| ~~**PR-3: Login throttling**~~ | ~~Enable `login_throttling: max_attempts: 5` in security.yaml.~~ **Done.** | #5 |
| ~~**PR-4: Fix cron schedule**~~ | ~~Sync build/accept PO timing.~~ **Done.** | #7 |
| ~~**PR-5: Enable OPcache preload**~~ | ~~Uncomment in prod PHP config.~~ **Deferred** — PHP 8.5 behaviour change causes critical errors. | #11 |

### Phase 1: High-Leverage Refactors (1-2 weeks)

| PR | Description | Recommendations |
|---|---|---|
| ~~**PR-6: Async event decision**~~ | ~~Either implement `AsyncDomainEventInterface` on audit/pricing events, or remove dead code.~~ **Done.** Decision: keep for future use. | #3 |
| ~~**PR-7: Reporting handler consistency**~~ | ~~Refactor reporting handlers to use `FlusherInterface`.~~ **Done.** | #4 |
| ~~**PR-8: Command chaining refactor**~~ | ~~Replace `__invoke()` chaining with proper Symfony command invocation or shared services.~~ **Won't fix** — coupling acceptable, alternatives lose output fidelity. | #8 |
| ~~**PR-9: Centralized logging**~~ | ~~Set up log aggregation (CloudWatch/Loki). Configure alerts.~~ **Deferred.** | #6 |
| ~~**PR-10: Production runbook**~~ | ~~Write `Docs/11-runbook.md` with operational procedures.~~ **Done.** | #12 |

### Phase 2: Long-Term Quality (2-4 weeks)

| PR | Description | Recommendations |
|---|---|---|
| ~~**PR-11: Enrich event payloads**~~ | ~~Add key attributes to thin domain events.~~ **Won't fix** — thin by design, listeners need entity traversal regardless. | #9 |
| ~~**PR-12: Parameterized tests**~~ | ~~Add DataProviders for status transitions, price models, and validation rules.~~ **Done.** | #10 |
| ~~**PR-13: Customer test coverage**~~ | ~~Expand Customer context to 25+ tests.~~ **Deferred** — auth flows to be rewritten first. | #15 |
| ~~**PR-14: Accessibility audit**~~ | ~~Add aria-labels, skip-nav, and focus management.~~ **Done.** | #14 |
| ~~**PR-15: Note context ADR**~~ | ~~Document architectural decisions for the ticket system.~~ **Done.** | #13 |

---

## K) Questions

None blocking. All findings are based on concrete code evidence and documented patterns.
