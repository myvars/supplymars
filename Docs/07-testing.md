# Testing

How tests are run, written and checked here. Read it before adding a feature, and when a test fails only under `make test` or in CI.

## Rules

- **A feature is not done until a test exercises it the way a caller would:** an HTTP request for a controller, a handler call for a handler, a flush for a listener on a domain event, a message read from the transport for anything queued. A test that only shows the code did not throw does not count.
- **Good coverage** means the failure cases as well as the success case: not found, invalid input, a refused user, a bad CSRF token. Nothing measures a coverage percentage.
- **Clean up uploads.** Files stored through `UploadHelper` are outside the database transaction, so DAMA does not roll them back: delete them at the end of each test that uploads. In the test environment they go to `var/cache/test/storage/`, not `public/`, so a missed one never shows in the dev app.
- **No deprecations, notices or warnings from `src/`.** `phpunit.xml.dist` sets `failOnDeprecation`, `failOnNotice` and `failOnWarning`, and its `<source>` block (`restrictNotices`, `restrictWarnings`, `ignoreIndirectDeprecations`) limits them to ones triggered from `src/`, so any of those fails the run.
- **Final classes can be mocked:** `DG\BypassFinals` runs as a PHPUnit extension (`phpunit.xml.dist`).
- **A failing flow test saves the page it was on:** Browser's `BrowserExtension` (`phpunit.xml.dist`) writes it to `var/browser/source/`, named after the test (a serial run also lists the files at the end). `var/` is a volume of the `php` container, so after `make test` read them with `make bash`. In CI, a failed run uploads them as the `browser-failures` artifact.
- **A test must not depend on another's files, cache or order** (next section).

### In Parallel

`make test`, `make check` and CI run the suite through `scripts/run-tests.sh`, which uses ParaTest to start one PHPUnit process per CPU. ParaTest reads the same `phpunit.xml.dist`, so the extensions and the fail-on-deprecation settings apply to every process. It gives each process a `TEST_TOKEN` (1, 2, 3…), and `config/packages/doctrine.yaml` appends it to the test database name (`dbname_suffix`), so each process has its own database (`app_test1`, `app_test2`…) and DAMA rolls back inside it as usual. The script creates and resets these databases before each run.

`TEST_PROCESSES` sets the number of processes, e.g. `TEST_PROCESSES=2 make test`. Lower it if the Docker VM runs short of memory.

Tests run in separate processes at the same time, so a test must not rely on anything another test leaves behind or changes outside the database:

- **Files:** the upload storage folder is shared by all processes, so each file needs a unique name. `UploadHelper` adds a random suffix to each stored file. The backups folder is per process (`test_backups_dir` in `config/packages/oneup_flysystem.yaml`), because the backup tests empty it in `setUp()`; a test that clears a folder needs the same.
- **Cache:** in the test environment the app cache is in memory (`cache.adapter.array`, `config/packages/cache.yaml`), not Redis, so each process and each kernel boot starts empty and cached values such as the sidebar badge counts never pass between tests. Rate-limit and login-throttling counts are in a separate pool, `cache.rate_limiter`, an `ArrayAdapter` in test (same file, `when@test`): it is not cleared between the requests of one test, and is empty on each kernel boot. The browser reboots the kernel per request, so a test that needs counts to carry over uses `->disableReboot()` (`RateLimitTest`).
- **Locks:** a command test that takes a lock (e.g. `BackupDatabaseCommandTest`) runs its tests one after another, because a test class stays in one process.

A failure that only appears in parallel usually means two tests share one of these, or that a test depends on the order tests run in. Plain `vendor/bin/phpunit` runs serially against `app_test` (below), which helps tell the two apart.

## How to

### Run the tests

```bash
make test                          # all tests, in parallel, in Docker
make test-CreateManufacturerFlowTest   # filtered (passed to --filter)
```

### Locally

```bash
# Create a fresh app_test (rerun all three after an entity change; make test resets only app_test1…N)
symfony console doctrine:database:drop --if-exists --force --env=test
symfony console doctrine:database:create --env=test
symfony console doctrine:schema:create --env=test

vendor/bin/phpunit                                            # all, serially
vendor/bin/phpunit tests/Catalog/UI/CreateManufacturerFlowTest.php   # one file
vendor/bin/phpunit --filter testSuccessfulCreationViaForm     # one method
```

### Write a test

Tests mirror `src/`. Copy the nearest existing test.

| Type | Base class | Location | Example |
|------|-----------|----------|---------|
| Unit (entity rules, events raised) | `TestCase` | `tests/{Context}/Domain/`, named `{Class}DomainTest` | `tests/Order/Domain/CustomerOrderDomainTest.php` |
| Handler | `KernelTestCase` | `tests/{Context}/Application/Handler/` | `tests/Order/Application/Handler/CreateOrderHandlerTest.php`: invokes the handler (`($this->handler)($command)`), reads the order back through `OrderRepository` and checks its fields, and covers the customer-not-found and missing-VAT-rate failures |
| Listener | `KernelTestCase` | `tests/{Context}/Application/Listener/` | `tests/Pricing/Application/Listener/CategoryPricingWasChangedListenerTest.php` |
| Service | `KernelTestCase` | `tests/{Context}/Application/Service/` | `tests/Purchasing/Application/Service/OrderAllocatorTest.php`, `OrderItemAllocatorTest.php` (sourcing) |
| Console | `KernelTestCase` | `tests/{Context}/UI/Console/`, named `{Command}Test` | `tests/Audit/UI/Console/PruneStatusChangeLogCommandTest.php`. A command test that also calls the app over HTTP keeps `KernelTestCase` and adds `HasBrowser` (`tests/Customer/UI/Console/IssueApiTokenCommandTest.php`) |
| Repository and mapping | `KernelTestCase` | `tests/{Context}/Infrastructure/`, matching `src/` (a repository under `Persistence/Doctrine/`) | `tests/Customer/Infrastructure/Persistence/Doctrine/UserDoctrineRepositoryInsightsTest.php`; mapping cases in `tests/Catalog/Infrastructure/ProductUpdateDeleteMappingTest.php`. Only for a query, or a mapping decision (cascade, orphan removal, `onDelete`, a unique key), whose effect a handler test would not show; `doctrine:schema:validate` already checks the mapping against the schema |
| Flow (web) | `WebTestCase` + `HasBrowser` | `tests/{Context}/UI/`, named `{Action}{Entity}FlowTest` | `tests/Catalog/UI/CreateManufacturerFlowTest.php`, `UpdateManufacturerFlowTest.php`. A page that is not a FormFlow action is `{Controller}Test`, in the same folder (`tests/Reporting/UI/ReportsControllerTest.php`); a test of behaviour across all pages is named after it (`tests/Shared/UI/Http/SecurityHeadersTest.php`). Shared tests mirror `src/Shared/UI/Http/` |
| API | as flow | `tests/{Context}/UI/Api/` | `tests/Order/UI/Api/OrderApiTest.php`; token handling and error format in `tests/Shared/UI/Http/Api/ApiAuthenticationTest.php` |

For pricing cascades, see `tests/Pricing/Application/Handler/UpdateCategoryCostHandlerTest.php` and the listener tests beside the one above.

An API test logs in with `->actingAs($user, 'api')`, which attaches the user to the `api` firewall. Public catalog endpoints need no login (`CatalogApiTest`). The API itself is described in [patterns/API/README.md](patterns/API/README.md).

### Conventions

How each test is written. Some tests predate a rule; bring a test in line when you change it.

| Topic | Convention |
|-------|------------|
| Class | `final class`. Rector does not enforce it for test cases, so check it in review |
| One class, one file | One test file per class under test, in the folder that matches `src/`. Add a case to the existing file rather than starting a second one about a topic |
| Unit test name | `{Class}DomainTest` for a class in `Domain/` (`OrderStatusDomainTest`, `SalesDurationDomainTest`), `{Class}Test` elsewhere |
| Method | camelCase `testXxx` that says what happens: `testFailsWhenOrderItemNotFound` |
| Assertions | `self::assert*()` (they are static); the few instance assertions, such as `$this->assertCommandIsSuccessful()`, on `$this`. Expected exceptions: `$this->expectException()` and `$this->expectExceptionMessageIsOrContains()`, which are instance methods, before the call that throws |
| Handler setup | Get the handler from the container in `setUp()` and call it: `($this->handler)($command)` |
| Logging in, handler and kernel tests | `#[WithStory(StaffUserStory::class)]` (or `SuperAdminUserStory`) on the class. Put it on each method instead only when one test must run with no one logged in (`RepublishReviewHandlerTest::testFailsWhenNoModeratorAuthenticated`) or tests need different stories (the super-admin delete handlers) |
| Logging in, flow tests | `->actingAs(UserFactory::new()->asStaff()->create())`. It logs in the browser only. Add `#[WithStory(StaffUserStory::class)]` as well when the fixtures change a status, which the audit logs against the current user in the test's own kernel (`PurchaseOrderLiveUpdatesFlowTest`, `ShowOrderFlowTest::testProcessingOrderDoesNotOfferCancel`); without it they fail with "No authenticated user found" |
| Fixtures | In kernel and flow tests, Foundry factories and their states: add a state to the factory rather than `X::create()` + `persist()` + `flush()`, or setting a field by reflection. Unit tests build the entity through its own methods |
| Reading back | `XFactory::assert()->exists()`, `->count()`, `->empty()`, `->notExists()` for existence and counts. For field values after a browser request, read the object the factory returned: auto-refresh (`enable_auto_refresh_with_lazy_objects`) reloads it from the database when the kernel resets (`tests/Note/UI/TicketStatusFlowTest.php`). It does not notice a deleted row, which keeps its old values, so check a deletion with `->notExists()` or `find()`. In a kernel test that calls the code directly, the object is the one the code changed in memory, so to prove a change was saved, `flush()` and `clear()` and read it again with `XFactory::repository()->find($id)`; auto-refresh does not reload it there |
| Test doubles | `createStub()` unless the test asserts a call; a mock only where it has an expectation. Use the real value object (`ProductPublicId::new()`, `VatRate::create()`) rather than a double of it |
| Console | `self::runCommand('app:…', [...])` (Symfony 8.1, on `KernelTestCase`) returns an `ExecutionResult`; check it with `$this->assertCommandIsSuccessful($result)`, `assertCommandFailed()` or `assertCommandIsInvalid()`, and read `$result->getDisplay()`. Older tests build a `CommandTester` and compare the status code by hand |
| Time | Code that needs the current time reads a `ClockInterface`; its test sets the time with Symfony's `ClockSensitiveTrait` (`static::mockTime()`) or a `MockClock`, never compares with `new \DateTimeImmutable()` |

### Factories and stories

Factories are in `tests/Shared/Factory/` (Zenstruck Foundry), one per entity; most create the related entities they need. `FoundryExtension` in `phpunit.xml.dist` boots Foundry for every test, so a test class needs no trait; the old `Factories` trait is deprecated and goes in Foundry 3. States worth knowing:

| Factory | State | Gives |
|---------|-------|-------|
| `UserFactory` | `asStaff()` | Staff user (`setStaff(true)` adds `ROLE_ADMIN`) |
| `UserFactory` | `asSuperAdmin()` | Staff user with `ROLE_ADMIN` and `ROLE_SUPER_ADMIN` |
| `UserFactory` | `withApiToken()` | Stores the hash of a Bearer token for API tests; send the plain token (default `test-api-token`) |
| `VatRateFactory` | `withStandardRate()` | The standard rate, which order creation looks up |
| `ProductFactory` | `withActiveSource(?SupplierProduct)` | Adds a supplier product (a new one if none given) after persist (`afterPersist`), so the product has an active source |
| `SupplierFactory` | `asWarehouse()` | Marks the supplier as the warehouse |
| `ProductReviewFactory` | `published()`, `hidden()` | A review in that status |
| `PurchaseOrderItemFactory` | `delivered()` | An item taken through each status to delivered, with `deliveredAt` now; its purchase order and customer order item follow, as in the domain. The status changes are audited, so log in first (`StaffUserStory`) |

Shared related entities are created lazily with `LazyValue::memoize()`, so one is made only when the test does not pass its own; see `tests/Shared/Factory/CategoryFactory.php`.

Log in with `->actingAs(UserFactory::new()->asStaff()->create())` in a flow test, or with a story on the test class ([Conventions](#conventions)): `#[WithStory(StaffUserStory::class)]` or `#[WithStory(SuperAdminUserStory::class)]` (`tests/Shared/Story/`). Each creates the `DefaultUserAuthenticator` default user with that role and logs it in, which is what a handler test needs when the handler reads the current user. The delete handlers that require `ROLE_SUPER_ADMIN` ([08-security.md](08-security.md#what-only-a-super-admin-can-do)) need the super-admin story or state; other deletes need only staff.

### Assert a message was queued

The `async` transport is `in-memory://` in test (`config/packages/messenger.yaml`, `when@test`), so nothing is stored and no worker runs. Read what was sent from the test container:

```php
$transport = self::getContainer()->get('messenger.transport.async');
self::assertInstanceOf(InMemoryTransport::class, $transport);

$sent = $transport->getSent();
self::assertCount(1, $sent);
self::assertInstanceOf(SendEmailMessage::class, $sent[0]->getMessage());
```

In a flow test, call `->interceptRedirects()` before the action and don't follow the redirect: the next request starts a new kernel with an empty transport. Full example: `ResetPasswordFlowTest::testRequestingAResetQueuesTheEmail` (`tests/Customer/UI/ResetPasswordFlowTest.php`).

The transport serializes each message (`in-memory://?serialize=true`), as the doctrine queue does, so a message or stamp that cannot be stored fails here rather than in production. `getSent()` counts every message, so changing an order also queues a `RefreshOrderPage`: filter by class rather than assume one message.

To run what was queued, as the worker would, and see what reached the Mercure hub, use `tests/Shared/Mercure/QueuedNotifications` ([Turbo](patterns/Turbo/README.md#testing)).

### Running All Checks

`make check` runs every check CI runs, with the same commands; `make check-<step>` runs one. Run it before opening a pull request. Every step but `prod-config` runs `scripts/check.sh` in the `php` container; `prod-config` runs on the host, with Docker.

| Step | What it runs |
|------|--------------|
| `prod-config` | `docker compose config` on the production compose files (`compose.prod.yaml`, `compose.publish.yaml`), for the live stack and the `playground` profile; `frankenphp validate` on `docker/frankenphp/Caddyfile` with `SERVER_NAME` `localhost` and `:80`, and again in worker mode with the `FRANKENPHP_CONFIG` from `compose.prod.yaml`. Only the publish and deploy jobs otherwise use these files, so a mistake would first show on a deploy |
| `cs` | `php-cs-fixer fix --dry-run --diff` (`.php-cs-fixer.dist.php`) |
| `rector` | `rector process --dry-run` (sets and paths in `rector.php`) |
| `phpstan` | `phpstan analyse`, level 7 (`phpstan.dist.neon`), after warming the dev container it reads |
| `deptrac` | `deptrac analyse`: the [rules between contexts](03-architecture.md#rules-between-contexts) in `deptrac.yaml` |
| `lint` | `lint:container` (test and dev), `lint:twig templates/`, `lint:yaml config/` |
| `audit` | `composer audit`: known vulnerabilities and abandoned packages. Between runs, GitHub's Dependabot security alerts (on for this repository, no update pull requests) report a new advisory for a locked package |
| `migrations` | Runs every migration on an empty database (`app_test_migrations`), then `doctrine:schema:validate` |
| `tests` | `scripts/run-tests.sh` (see [In Parallel](#in-parallel)) |

When a step fails, fix the code rather than the check: `php-cs-fixer fix` and `rector process` (without `--dry-run`) apply their own changes, and `make:migration` generates a missing migration.

CI (`.github/workflows/ci.yml`) runs each step as its own `make check-<step>`, so a failure names the step. It keeps the `php` image's layers in the GitHub Actions cache (`compose.ci.yaml`), so a run rebuilds only the layers that changed: after an edit to `docker/Dockerfile` or a file it copies, or when GitHub has evicted the cache (unused for 7 days).

## Gotchas

- **The tests build their schema with `doctrine:schema:create`, not the migrations,** so the `migrations` step is what proves the migration files work. An entity change with no migration passes the tests and fails there. The first migration creates the whole schema; on a database that already has it (live and playground) it adds no SQL and is only recorded.
- **Keep `vendor/` in step with `composer.lock`.** CI installs it fresh; the container installs only when `vendor/` is missing, so a stale local `vendor/` passes checks CI fails.
- **`composer audit` can fail a run that changed nothing:** a new advisory fails the next run.
- **A local `app_test` goes stale** after an entity change; recreate it ([Locally](#locally)). `make test` resets only `app_test1…N`.
- **A state leak between requests passes ordinary flow tests,** which boot a fresh kernel per request. `StateBetweenRequestsTest` guards it; see [State Between Requests](03-architecture.md#state-between-requests).
- **`importmap.php` is excluded from PHP-CS-Fixer and Rector,** because `importmap:require` rewrites it.

## Related

- [Architecture](03-architecture.md): layers, events, the queue, state between requests
- [API](patterns/API/README.md): endpoints and authentication the API tests cover
- [Local setup](02-setup-local.md)
