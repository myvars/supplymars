# Testing Guide

This document covers the testing strategy, test types, and how to write effective tests for SupplyMars.

## Test Strategy

### Philosophy

SupplyMars follows a **testing pyramid** approach:

```
           ┌─────────┐
          /  E2E/UI   \       Few, slow, high confidence
         /─────────────\
        /  Integration  \     Moderate, test boundaries
       /─────────────────\
      /       Unit        \   Many, fast, focused
     └─────────────────────┘
```

### Test Distribution

| Type | Purpose | Execution Speed | Coverage Focus |
|------|---------|-----------------|----------------|
| Unit | Domain logic | Fast | Business rules |
| Integration | Handler/Repository | Medium | Data flow |
| Functional | HTTP endpoints | Slow | User journeys |

### Key Principles

1. **Test behavior, not implementation:** Focus on what code does, not how
2. **Use factories for data:** Foundry factories ensure consistent test data
3. **Transaction rollback:** DAMA bundle isolates each test
4. **Clean up side effects:** File uploads (via `UploadHelper`) persist outside DB transactions — delete them at the end of each test that uploads
5. **Meaningful assertions:** Test outcomes, not internal state

## Test Types

### Unit Tests

**Purpose:** Test domain logic in isolation without database or services.

**Location:** `tests/{Context}/Domain/`

**Characteristics:**
- Extend `PHPUnit\Framework\TestCase`
- Use stubs/mocks for dependencies
- No database access
- Fastest execution

**Example:**
```php
// tests/Order/Domain/OrderDomainTest.php

class OrderDomainTest extends TestCase
{
    public function testCreateFromCustomerSetsDefaultStatus(): void
    {
        $order = CustomerOrder::createFromCustomer(
            customer: $this->stubUser(),
            shippingMethod: ShippingMethod::THREE_DAY,
            vatRate: $this->stubVatRate(),
            customerOrderRef: 'TEST-001',
        );

        self::assertSame(OrderStatus::PENDING, $order->getStatus());
    }

    private function stubUser(): User
    {
        $user = $this->createStub(User::class);
        $user->method('getDefaultShippingAddress')
            ->willReturn($this->createStub(Address::class));
        return $user;
    }
}
```

### Integration Tests

**Purpose:** Test application services with real database transactions.

**Location:** `tests/{Context}/Application/Handler/`

**Characteristics:**
- Extend `Symfony\Bundle\FrameworkBundle\Test\KernelTestCase`
- Use `Factories` trait for test data
- Real database operations (rolled back after test)
- Test handler → repository → database flow

**Example:**
```php
// tests/Order/Application/Handler/CreateOrderHandlerTest.php

class CreateOrderHandlerTest extends KernelTestCase
{
    use Factories;

    private CreateOrderHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreateOrderHandler::class);
    }

    public function testHandleCreatesOrder(): void
    {
        $customer = UserFactory::createOne();
        $vatRate = VatRateFactory::new()->withStandardRate()->create();

        $command = new CreateOrder(
            customerId: $customer->getId(),
            shippingMethod: ShippingMethod::THREE_DAY,
            customerOrderRef: 'TEST-001'
        );

        $result = $this->handler->handle($command);

        self::assertTrue($result->ok);
        self::assertNotNull($result->payload);
    }
}
```

### Functional Tests (HTTP)

**Purpose:** Test complete request/response cycles through controllers.

**Location:** `tests/{Context}/UI/`

**Characteristics:**
- Extend `Symfony\Bundle\FrameworkBundle\Test\WebTestCase`
- Use `HasBrowser` trait for fluent assertions
- Test form submissions, redirects, flash messages
- Slowest but highest confidence

**Example:**
```php
// tests/Catalog/UI/ManufacturerFlowTest.php

class ManufacturerFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    #[WithStory(StaffUserStory::class)]
    public function testCreateManufacturer(): void
    {
        $this->browser()
            ->visit('/catalog/manufacturer/new')
            ->fillField('manufacturer[name]', 'Test Manufacturer')
            ->check('manufacturer[isActive]')
            ->click('Save')
            ->assertOn('/catalog/manufacturer/')
            ->assertSuccessful()
            ->assertSee('Manufacturer created');
    }

    #[WithStory(StaffUserStory::class)]
    public function testEditManufacturer(): void
    {
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Original']);

        $this->browser()
            ->visit("/catalog/manufacturer/{$manufacturer->getPublicId()}/edit")
            ->fillField('manufacturer[name]', 'Updated Name')
            ->click('Save')
            ->assertOn('/catalog/manufacturer/')
            ->assertSee('Manufacturer updated');
    }
}
```

### API Tests

**Purpose:** Test JSON API endpoints, authentication, error handling, and response structure.

**Location:** `tests/{Context}/UI/Api/` and `tests/Shared/UI/Http/Api/`

**Characteristics:**
- Extend `WebTestCase` with `HasBrowser` and `Factories` traits
- Public endpoints (Catalog) tested without authentication
- Authenticated endpoints use `->actingAs($user, 'api')` to attach the API firewall
- Assert JSON structure, status codes, pagination meta, and RFC 7807 error responses
- Named `{Context}ApiTest.php`

**Example:**
```php
// tests/Order/UI/Api/OrderApiTest.php

final class OrderApiTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testOrderIndexRequiresAuthentication(): void
    {
        $this->browser()
            ->get('/api/v1/orders')
            ->assertStatus(401);
    }

    public function testCreateOrderReturns201(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $customer = UserFactory::createOne();
        AddressFactory::createOne([
            'customer' => $customer,
            'isDefaultBillingAddress' => true,
            'isDefaultShippingAddress' => true,
        ]);
        VatRateFactory::new()->withStandardRate()->create();

        $this->browser()
            ->actingAs($user, 'api')
            ->post('/api/v1/orders', HttpOptions::json([
                'customer' => $customer->getPublicId()->value(),
                'shippingMethod' => 'THREE_DAY',
            ]))
            ->assertStatus(201)
            ->assertJson()
            ->assertJsonMatches('"data"."id" != null', true);
    }
}
```

**Key test files:**
- `tests/Catalog/UI/Api/CatalogApiTest.php` — Public catalog API (products, categories, subcategories, manufacturers)
- `tests/Order/UI/Api/OrderApiTest.php` — Authenticated order CRUD and item management
- `tests/Shared/UI/Http/Api/ApiAuthenticationTest.php` — Token auth, error format (RFC 7807), validation errors

## How to Run Tests

### Via Docker (Recommended)

```bash
# Run all tests
make test

# Run filtered tests
make test-ManufacturerFlowTest
make test-CreateOrder
```

### Locally

```bash
# Ensure test database exists
symfony console doctrine:database:create --env=test
symfony console doctrine:schema:create --env=test

# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/Catalog/UI/ManufacturerFlowTest.php

# Run specific test method
vendor/bin/phpunit --filter testCreateManufacturer

# Run tests in a directory
vendor/bin/phpunit tests/Order/
```

### Test Configuration

**phpunit.xml.dist:**
```xml
<phpunit colors="true"
         failOnDeprecation="true"
         failOnNotice="true"
         failOnWarning="true"
         bootstrap="tests/bootstrap.php">
    <php>
        <server name="APP_ENV" value="test" force="true"/>
        <env name="SYMFONY_DEPRECATIONS_HELPER" value="disabled"/>
    </php>

    <extensions>
        <bootstrap class="Zenstruck\Foundry\PHPUnit\FoundryExtension"/>
        <bootstrap class="DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension"/>
        <bootstrap class="DG\BypassFinals\PHPUnitExtension"/>
    </extensions>
</phpunit>
```

## Test Factories (Foundry)

### Location

All factories are in `tests/Shared/Factory/`.

### Available Factories

| Factory | Entity | Notable Methods |
|---------|--------|-----------------|
| `UserFactory` | User | `asStaff()` - creates admin user |
| `VatRateFactory` | VatRate | `withStandardRate()` - 20% VAT |
| `CategoryFactory` | Category | Auto-creates VAT rate |
| `SubcategoryFactory` | Subcategory | Auto-creates Category |
| `ManufacturerFactory` | Manufacturer | - |
| `ProductFactory` | Product | `withActiveSource()` - adds supplier product |
| `SupplierFactory` | Supplier | `asWarehouse()` - marks as warehouse |
| `SupplierProductFactory` | SupplierProduct | Auto-creates Supplier |
| `CustomerOrderFactory` | CustomerOrder | Auto-creates Customer |
| `CustomerOrderItemFactory` | CustomerOrderItem | Auto-creates Order, Product |
| `PurchaseOrderFactory` | PurchaseOrder | Auto-creates Order, Supplier |
| `PurchaseOrderItemFactory` | PurchaseOrderItem | Auto-creates PO, OrderItem |
| `ProductReviewFactory` | ProductReview | `published()` - approved review, `hidden()` - hidden review |
| `ProductReviewSummaryFactory` | ProductReviewSummary | - |

### Factory Usage

**Basic creation:**
```php
// Create single entity
$manufacturer = ManufacturerFactory::createOne();

// Create with custom attributes
$manufacturer = ManufacturerFactory::createOne([
    'name' => 'Custom Name',
    'isActive' => false,
]);

// Create multiple
$manufacturers = ManufacturerFactory::createMany(5);
```

**Using factory states:**
```php
// Staff user
$admin = UserFactory::new()->asStaff()->create();

// Warehouse supplier
$warehouse = SupplierFactory::new()->asWarehouse()->create();

// Standard VAT rate
$vatRate = VatRateFactory::new()->withStandardRate()->create();
```

**Lazy values (deferred creation):**
```php
// Category created only if not provided
protected function defaults(): array
{
    return [
        'category' => LazyValue::memoize(
            fn () => CategoryFactory::createOne()
        ),
    ];
}
```

**After instantiate hooks:**
```php
public function withActiveSource(): self
{
    return $this->afterInstantiate(function (Product $product): void {
        $sp = SupplierProductFactory::createOne(['product' => null]);
        $product->addSupplierProduct($this->markupCalculator, $sp);
    });
}
```

## Test Stories

### Purpose

Stories are reusable test narratives that set up common scenarios.

### Location

`tests/Shared/Story/`

### StaffUserStory

The most commonly used story - creates an authenticated admin user:

```php
// tests/Shared/Story/StaffUserStory.php

final class StaffUserStory extends Story
{
    public function __construct(
        private readonly DefaultUserAuthenticator $authenticator
    ) {}

    public function build(): void
    {
        UserFactory::new(['email' => $this->authenticator->getDefaultEmail()])
            ->asStaff()
            ->create();

        $this->authenticator->ensureAuthenticated();
    }
}
```

### Using Stories

**Via attribute (preferred):**
```php
#[WithStory(StaffUserStory::class)]
public function testAdminOnlyFeature(): void
{
    // Test runs with authenticated admin user
}
```

**Programmatically:**
```php
public function testSomething(): void
{
    StaffUserStory::load();
    // User is now authenticated
}
```

## Testing Sourcing/Pricing Logic

### Testing Price Calculation

```php
class PriceCalculationTest extends KernelTestCase
{
    use Factories;

    public function testProductPriceCalculation(): void
    {
        // Setup: Create category with known markup
        $vatRate = VatRateFactory::new()->withStandardRate()->create(); // 20%
        $category = CategoryFactory::createOne([
            'vatRate' => $vatRate,
            'defaultMarkup' => '25.000', // 25%
            'priceModel' => PriceModel::PRETTY_99,
        ]);

        // Create product with known cost
        $supplier = SupplierFactory::createOne();
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'cost' => '10.00',
            'stock' => 100,
        ]);

        $product = ProductFactory::new([
            'category' => $category,
        ])->withActiveSource($supplierProduct)->create();

        // Expected: 10.00 * 1.25 * 1.20 = 15.00, rounded to 15.99
        self::assertSame('15.99', $product->getSellPriceIncVat());
    }
}
```

### Testing Order Allocation

```php
class OrderAllocationTest extends KernelTestCase
{
    use Factories;

    public function testOrderSplitsAcrossSuppliers(): void
    {
        // Setup suppliers with limited stock
        $warehouse = SupplierFactory::new()->asWarehouse()->create();
        $dropshipper = SupplierFactory::createOne();

        $product = ProductFactory::createOne();

        SupplierProductFactory::createOne([
            'supplier' => $warehouse,
            'product' => $product,
            'stock' => 5,
            'cost' => '10.00',
        ]);

        SupplierProductFactory::createOne([
            'supplier' => $dropshipper,
            'product' => $product,
            'stock' => 10,
            'cost' => '9.00', // Cheaper
        ]);

        // Create order for 12 units
        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 12,
        ]);

        // Run allocation
        $allocator = self::getContainer()->get(OrderAllocator::class);
        $allocator->process($order);

        // Verify: Dropshipper gets 10 (cheaper), warehouse gets 2
        $purchaseOrders = $order->getPurchaseOrders();
        self::assertCount(2, $purchaseOrders);

        // Check outstanding is 0
        self::assertSame(0, $orderItem->getOutstandingQty());
    }
}
```

### Testing Price Cascades

```php
class PriceCascadeTest extends KernelTestCase
{
    use Factories;

    public function testCategoryMarkupChangeAffectsProducts(): void
    {
        $category = CategoryFactory::createOne([
            'defaultMarkup' => '20.000',
        ]);

        $product = ProductFactory::new([
            'category' => $category,
        ])->withActiveSource()->create();

        $originalPrice = $product->getSellPriceIncVat();

        // Change category markup
        $handler = self::getContainer()->get(UpdateCategoryCostHandler::class);
        $handler->handle(new UpdateCategoryCost(
            id: $category->getPublicId(),
            defaultMarkup: '30.000',
            priceModel: $category->getPriceModel(),
            isActive: true,
        ));

        // Refresh product from database
        self::getContainer()->get('doctrine')->getManager()->refresh($product);

        // Price should have increased
        self::assertGreaterThan($originalPrice, $product->getSellPriceIncVat());
    }
}
```

## What "Good Coverage" Means

### Coverage Targets

| Layer | Target | Reasoning |
|-------|--------|-----------|
| Domain | 90%+ | Critical business rules |
| Handlers | 80%+ | Main application logic |
| Controllers | 60%+ | Integration verified by handler tests |
| Infrastructure | 50%+ | Mostly Doctrine boilerplate |

### What to Test

**Always test:**
- Domain entity creation and state changes
- Business rule enforcement
- Status transitions and validations
- Price calculations
- Event raising

**Test selectively:**
- CRUD operations (via functional tests)
- Form validation (via functional tests)
- Repository queries (integration tests)

**Skip testing:**
- Doctrine generated code
- Symfony form types (unless custom logic)
- Getter/setter only methods

### Test Quality Indicators

1. **Tests are fast:** Unit tests < 10ms, integration < 100ms
2. **Tests are independent:** Run in any order
3. **Tests are readable:** Clear arrange/act/assert
4. **Tests have one reason to fail:** Single assertion focus
5. **Tests use factories:** Not raw SQL or fixtures

## Code Quality Tools

SupplyMars enforces code quality through three complementary tools, all run as part of CI.

### PHPStan (Static Analysis)

**Level:** 7 (strict)

PHPStan catches type errors, undefined methods, and other issues before runtime.

```bash
# Run analysis
vendor/bin/phpstan analyse

# With memory limit for large codebases
vendor/bin/phpstan analyse --memory-limit=512M
```

**Configuration:** `phpstan.dist.neon`

Key settings:
- Level 7 analysis (strict type checking)
- Symfony extension enabled
- Doctrine extension enabled for entity analysis

### PHP-CS-Fixer (Code Style)

Enforces consistent code formatting across the codebase.

```bash
# Fix all files
vendor/bin/php-cs-fixer fix

# Dry run (show what would change)
vendor/bin/php-cs-fixer fix --dry-run --diff
```

**Configuration:** `.php-cs-fixer.dist.php`

Key rules:
- `@Symfony` ruleset as base
- `yoda_style: false` - use `$value === null`, not `null === $value`
- Single space around concatenation operators
- Strict types declaration required

### Rector (Automated Refactoring)

Rector applies automated code improvements and keeps the codebase modern.

```bash
# Apply changes
vendor/bin/rector process

# Dry run (show what would change)
vendor/bin/rector process --dry-run
```

**Configuration:** `rector.php`

Active rule sets:
- Dead code removal
- Code quality improvements
- Type declaration additions
- Doctrine-specific rules
- Symfony-specific rules

### Running All Tools

```bash
# Run all quality checks (typical pre-commit workflow)
vendor/bin/php-cs-fixer fix
vendor/bin/rector process
vendor/bin/phpstan analyse
vendor/bin/phpunit
```

These tools run automatically in GitHub Actions CI on every push and pull request.
