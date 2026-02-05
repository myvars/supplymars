# FormFlow Usage Guide

This document provides canonical examples, usage patterns, and rules for working with the FormFlow pattern.

## Controller Patterns

### Pattern 1: Create Form

```php
#[Route(path: '/product/new', name: 'app_catalog_product_new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    CreateProductMapper $mapper,
    CreateProductHandler $handler,
    FormFlow $flow,
): Response {
    return $flow->form(
        request: $request,
        formType: ProductType::class,
        data: new ProductForm(),
        mapper: $mapper,
        handler: $handler,
        context: FlowContext::forCreate(self::MODEL),
    );
}
```

Key points:
- `ProductType::class` — Symfony form type
- `new ProductForm()` — Empty DTO for form binding
- `$mapper` — Transforms form data to command
- `$handler` — Executes the command, returns `Result`
- `FlowContext::forCreate()` — Sets operation, derives template and success route

### Pattern 2: Update Form with Delete Button

```php
#[Route(path: '/product/{id}/edit', name: 'app_catalog_product_edit', methods: ['GET', 'POST'])]
public function edit(
    Request $request,
    #[ValueResolver('public_id')] Product $product,
    UpdateProductMapper $mapper,
    UpdateProductHandler $handler,
    FormFlow $flow,
): Response {
    return $flow->form(
        request: $request,
        formType: ProductType::class,
        data: ProductForm::fromEntity($product),
        mapper: $mapper,
        handler: $handler,
        context: FlowContext::forUpdate(self::MODEL)->allowDelete(true),
    );
}
```

Key points:
- `ProductForm::fromEntity($product)` — Populate form from existing entity
- `->allowDelete(true)` — Shows delete button in template via `flowAllowDelete`

### Pattern 3: Delete Confirmation

```php
#[Route(path: '/product/{id}/delete/confirm', name: 'app_catalog_product_delete_confirm', methods: ['GET'])]
public function deleteConfirm(
    #[ValueResolver('public_id')] Product $product,
    DeleteFlow $flow,
): Response {
    return $flow->deleteConfirm(
        entity: $product,
        context: FlowContext::forDelete(self::MODEL),
    );
}
```

Key points:
- GET-only route for confirmation modal
- `$product` passed as `entity` for display in template

### Pattern 4: Delete Action

```php
#[Route(path: '/product/{id}/delete', name: 'app_catalog_product_delete', methods: ['POST'])]
public function delete(
    Request $request,
    #[ValueResolver('public_id')] Product $product,
    DeleteProductHandler $handler,
    DeleteFlow $flow,
): Response {
    return $flow->delete(
        request: $request,
        command: new DeleteProduct($product->getPublicId()),
        handler: $handler,
        context: FlowContext::forDelete(self::MODEL),
    );
}
```

Key points:
- POST-only route
- CSRF token validated automatically using `'delete' . $command->id`
- Command contains public ID, not entity reference

### Pattern 5: Command Execution (State Transition)

```php
#[Route(path: '/order/{id}/allocate', name: 'app_order_allocate', methods: ['GET'])]
public function allocate(
    Request $request,
    #[ValueResolver('public_id')] CustomerOrder $order,
    AllocateOrderHandler $handler,
    CommandFlow $flow,
): Response {
    return $flow->process(
        request: $request,
        command: new AllocateOrder($order->getPublicId()),
        handler: $handler,
        context: FlowContext::forSuccess('app_order_show', [
            'id' => $order->getPublicId()->value(),
        ]),
    );
}
```

Key points:
- No form, immediate action
- `FlowContext::forSuccess()` — Explicit success route
- Handler returns `Result` with message for flash

### Pattern 6: Paginated List

```php
#[Route(path: '/product/', name: 'app_catalog_product_index', methods: ['GET'])]
public function index(
    Request $request,
    SearchFlow $flow,
    ProductRepository $repository,
    #[MapQueryString] ProductSearchCriteria $criteria = new ProductSearchCriteria(),
): Response {
    return $flow->search(
        request: $request,
        repository: $repository,
        criteria: $criteria,
        context: FlowContext::forSearch(self::MODEL),
    );
}
```

Key points:
- `#[MapQueryString]` — Binds query params to criteria DTO
- `$repository` must implement `FindByCriteriaInterface`
- `FlowContext::forSearch()` — Sets operation to `Index`, derives template path
- Out-of-range pages redirect to page 1 automatically

### Pattern 7: Filter Form

```php
#[Route(path: '/product/search/filter', name: 'app_catalog_product_search_filter', methods: ['GET', 'POST'])]
public function searchFilter(
    Request $request,
    ProductFilterMapper $mapper,
    ProductFilterHandler $handler,
    FormFlow $flow,
    #[MapQueryString] ProductSearchCriteria $criteria = new ProductSearchCriteria(),
): Response {
    return $flow->form(
        request: $request,
        formType: ProductFilterType::class,
        data: $criteria,
        mapper: $mapper,
        handler: $handler,
        context: FlowContext::forFilter(self::MODEL),
    );
}
```

Key points:
- Filter forms bind existing criteria as initial data
- Handler typically returns `Result::ok()` with redirect to index

## Turbo Integration

### How It Works

The `TurboAwareRedirector` (`src/Shared/UI/Http/FormFlow/Redirect/TurboAwareRedirector.php`) detects Turbo requests and returns appropriate responses.

Detection criteria:
1. `turbo-frame` header present
2. Request format is Turbo stream
3. Accept header contains `text/vnd.turbo-stream.html`

When Turbo is detected:
- Instead of HTTP redirect, returns 200 with Turbo stream content
- Renders `shared/turbo/turbo_stream_refresh.html.twig`
- Sets content-type to `text/vnd.turbo-stream.html`

When Turbo is not detected:
- Standard `RedirectResponse` with configured status (default 303)

### Controller Implications

Controllers do not need to handle Turbo explicitly. The flows handle it automatically:

```php
// This works for both Turbo and non-Turbo requests
return $flow->form(
    request: $request,
    formType: ProductType::class,
    // ...
);
```

### Refresh Behavior

Use `redirectOptions(refresh: true)` when the entire page should refresh:

```php
context: FlowContext::forCreate(self::MODEL)
    ->redirectOptions(refresh: true, status: 303),
```

This passes the URL to the Turbo stream template for a full page refresh.

### Auto-Update Forms

The `AutoUpdateGuard` (`src/Shared/UI/Http/FormFlow/Guard/AutoUpdateGuard.php`) supports forms that submit automatically (e.g., on select change).

When a form has a button named `auto-update` and it was clicked:
- Form errors are cleared (for responsive UX)
- Form is not processed through handler
- Returns 200 (not 422) even with validation issues

Template usage:
```twig
<button type="submit" name="auto-update" hidden>Auto Update</button>
```

## Error Handling

### Result Object

Handlers return `Result` objects to communicate success or failure:

```php
// Success
return Result::ok('Product created successfully');

// Success with payload
return Result::ok('Product created', $product);

// Success with forced redirect
return Result::ok('Order allocated', redirect: new RedirectTarget(
    route: 'app_order_show',
    params: ['id' => $order->getPublicId()->value()],
));

// Failure
return Result::fail('Could not create product: SKU already exists');
```

### HTTP Status Codes

| Scenario | Status |
|----------|--------|
| GET (form display) | 200 |
| Valid POST, handler success | 303 (redirect) |
| Valid POST, handler failure | 422 |
| Invalid POST (validation) | 422 |
| Turbo redirect | 200 (with stream content) |

### Flash Messages

Flash types map to Bootstrap alert classes:

```php
// In FlashMessenger
success() → 'success'   // Green alert
warning() → 'warning'   // Yellow alert
error()   → 'danger'    // Red alert
```

Messages come from:
1. `Result::$message` — Handler's explicit message
2. Default messages — Generated from operation and model name

### CSRF Validation (DeleteFlow)

DeleteFlow validates CSRF tokens with ID `'delete' . $command->id`:

```php
// In delete template
<input type="hidden" name="_token" value="{{ csrf_token('delete' ~ result.publicId) }}">
```

If CSRF invalid:
- Flashes error message
- Redirects to success URL (no deletion occurs)
- Does not throw exception

## Do/Don't Rules

### Controllers

**Do:**
- Inject dependencies via constructor or method parameters
- Use named parameters for flow calls (improves readability)
- Use `#[ValueResolver('public_id')]` for entity resolution
- Define `private const MODEL = 'context/entity'` for reuse
- Return the flow's response directly

**Don't:**
- Contain business logic
- Call repositories directly (except SearchFlow)
- Manipulate forms directly
- Set flash messages
- Build responses manually

### Flows

**Do:**
- Handle HTTP concerns only
- Delegate business logic to handlers
- Use `FlowContext` for configuration
- Return proper status codes

**Don't:**
- Contain domain rules
- Access repositories
- Modify entities
- Throw domain exceptions

### Handlers

**Do:**
- Contain business logic
- Access repositories
- Modify entities
- Return `Result` objects
- Emit domain events

**Don't:**
- Access `Request` object
- Set flash messages
- Build responses
- Know about HTTP

### Mappers

**Do:**
- Transform form DTOs to commands
- Validate input shapes
- Be pure functions (no side effects)

**Don't:**
- Contain business logic
- Access repositories
- Modify entities

## Testing

### Controller Tests

Test routing, authentication, and authorization only:

```php
public function test_new_requires_authentication(): void
{
    $this->client->request('GET', '/product/new');
    $this->assertResponseRedirects('/login');
}

public function test_new_renders_form(): void
{
    $this->loginAsStaff();
    $this->client->request('GET', '/product/new');
    $this->assertResponseIsSuccessful();
    $this->assertSelectorExists('form');
}
```

### Handler Tests

Test business logic independently:

```php
public function test_creates_product(): void
{
    $command = new CreateProduct(
        name: 'Test Product',
        sku: 'TEST-001',
        // ...
    );

    $result = $this->handler->__invoke($command);

    $this->assertTrue($result->ok);
    $this->assertNotNull($this->repository->findBySku('TEST-001'));
}
```

### Flow Tests

Flows are tested once in the Shared module. Individual bounded contexts do not need to re-test flow behavior.
