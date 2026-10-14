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
        context: FlowContext::forCreate($this->model()),
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
        context: FlowContext::forUpdate($this->model())->allowDelete(true),
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
    ConfirmFlow $flow,
): Response {
    return $flow->confirm(
        entity: $product,
        context: FlowContext::forDelete($this->model()),
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
    ConfirmFlow $flow,
): Response {
    return $flow->execute(
        request: $request,
        command: new DeleteProduct($product->getPublicId()),
        handler: $handler,
        context: FlowContext::forDelete($this->model()),
    );
}
```

Key points:
- POST-only route
- CSRF token validated automatically using `confirmKey . $command->id` (key `'delete'` by default)
- Command contains public ID, not entity reference

### Pattern 4b: Confirmable Action (non-delete)

Same confirm-then-act shape as delete, but for a different action. `forConfirm`
namespaces the CSRF token to a custom key so it can't collide with delete:

```php
#[Route(path: '/purchase/order/{id}/rewind/confirm', name: 'app_purchasing_purchase_order_rewind_confirm', methods: ['GET'])]
public function rewindConfirm(
    #[ValueResolver('public_id')] PurchaseOrder $purchaseOrder,
    ConfirmFlow $flow,
): Response {
    return $flow->confirm(
        entity: $purchaseOrder,
        context: FlowContext::forConfirm($this->model(), 'rewind')
            ->template('purchasing/purchase_order/rewind.html.twig'),
    );
}

#[Route(path: '/purchase/order/{id}/rewind', name: 'app_purchasing_purchase_order_rewind', methods: ['POST'])]
public function rewind(
    Request $request,
    #[ValueResolver('public_id')] PurchaseOrder $purchaseOrder,
    RewindPurchaseOrderHandler $handler,
    ConfirmFlow $flow,
): Response {
    return $flow->execute(
        request: $request,
        command: new RewindPurchaseOrder($purchaseOrder->getPublicId()),
        handler: $handler,
        context: FlowContext::forConfirm($this->model(), 'rewind')
            ->successRoute('app_purchasing_purchase_order_show', ['id' => $purchaseOrder->getPublicId()->value()]),
    );
}
```

Key points:
- `forConfirm($model, key)` — token id becomes `key . $command->id` (e.g. `'rewind' . id`)
- The confirm template receives `confirmKey`; use `csrfId="{{ confirmKey }}{{ result.publicId.value }}"`
- Provide the confirm page via `->template()` (delete styling is not assumed)
- The other confirmable actions here are `cancel` (`OrderController`) and `remove` (`SupplierProductController`)

### Pattern 5: Command Execution (State Transition)

```php
#[Route(path: '/order/{id}/allocate', name: 'app_order_allocate', methods: ['GET'])]
public function allocate(
    Request $request,
    #[ValueResolver('public_id')] CustomerOrder $order,
    AllocateOrderHandler $handler,
    ActionFlow $flow,
): Response {
    return $flow->process(
        request: $request,
        command: new AllocateOrder($order->getPublicId()),
        handler: $handler,
        context: FlowContext::forAction('app_order_show', [
            'id' => $order->getPublicId()->value(),
        ]),
    );
}
```

Key points:
- No form, immediate action
- `FlowContext::forAction()` — Explicit success route
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
        adapter: $repository->findByCriteria($criteria),
        criteria: $criteria,
        context: FlowContext::forSearch($this->model()),
    );
}
```

Key points:
- `#[MapQueryString]` — Binds query params to criteria DTO
- `adapter` is any Pagerfanta `AdapterInterface`; the app's repositories return one from `findByCriteria()`
- `FlowContext::forSearch()` — Sets operation to `Index`, derives template path
- Out-of-range pages redirect to page 1 automatically

### Pattern 7: Filter Form

```php
#[Route(path: '/product/search/filter', name: 'app_catalog_product_search_filter', methods: ['GET', 'POST'])]
public function filter(
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
        context: FlowContext::forFilter($this->model()),
    );
}
```

Key points:
- Filter forms bind existing criteria as initial data
- Handler typically returns `Result::ok()` with redirect to index

## Turbo Integration

### How It Works

The `TurboAwareRedirector` (`vendor/myvars/form-flow/src/Redirect/TurboAwareRedirector.php`) detects Turbo requests and returns appropriate responses.

Detection: the request carries a `Turbo-Frame` header. A Turbo request made outside a frame gets a normal redirect.

When a Turbo Frame request is detected:
- Instead of HTTP redirect, returns 200 with Turbo stream content
- Generates stream inline (`<turbo-stream action="refresh">` or `<turbo-stream action="redirect">`)
- Sets content-type to `text/vnd.turbo-stream.html`

Otherwise:
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

Inside a Turbo Frame, a successful flow refreshes the current page in place. `FlowContext::forDelete()` and
`forConfirm()` navigate to the success route when its path differs from the page the request came from (for
example, deleting from a show page), and refresh otherwise. A handler `RedirectTarget` always navigates.
Without a Turbo Frame, every flow issues a 303 to the success route. There is no per-call refresh flag.

### Auto-Update Forms

The `AutoUpdateGuard` (`vendor/myvars/form-flow/src/Guard/AutoUpdateGuard.php`) supports forms that submit automatically (e.g., on select change).

When a form has a button named `auto-update` and it was clicked:
- Form errors are cleared (for responsive UX)
- Form is not processed through handler
- The form is re-rendered with status 422 (so Turbo renders the response), with its errors cleared

The button must be a form field, because the guard looks for a clickable form child named `auto-update`. A raw
HTML `<button>` in the template is not detected:

```php
$builder->add('auto-update', SubmitType::class, [
    'attr' => ['class' => 'hidden-submit-button', 'data-submit-form-target' => 'submit'],
]);
```

`ProductType`, `ProductFilterType`, `SupplierProductType` and `SupplierProductFilterType` use it: a field's `change->submit-form#submitForm` action clicks that hidden button.

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

Flash keys, rendered as `FlashToast` toasts:

```php
// In FlashMessenger
success() → 'success'
warning() → 'warning'
error()   → 'danger'
```

Messages come only from `Result::$message`; a null message means no flash. Two flows add their own:
ConfirmFlow flashes `Invalid CSRF token.` and SearchFlow flashes `Page N not found.`

### CSRF Validation (ConfirmFlow)

ConfirmFlow validates the token with id `confirmKey . $command->id`. The key defaults to
`'delete'` (via `forDelete`) and is overridable via `FlowContext::forConfirm($model, key)`:

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
- Define a private `model(): FlowModel` method returning `FlowModel::create(...)` (a class constant cannot call a static factory)
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
- Optionally set `Result.redirect` (a `RedirectTarget` route + params) when the success destination depends on the command's outcome
- Emit domain events

**Don't:**
- Access the `Request` object
- Set flash messages
- Build `Response` objects or render templates

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
// uses HasBrowser and Factories — see the *FlowTest classes under tests/*/UI/

public function testNewRequiresAuthentication(): void
{
    $this->browser()
        ->interceptRedirects()
        ->visit('/product/new')
        ->assertRedirectedTo('/login');
}

public function testNewRendersForm(): void
{
    $this->browser()
        ->actingAs(UserFactory::new()->asStaff()->create())
        ->visit('/product/new')
        ->assertSuccessful()
        ->assertSeeElement('form');
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

    $result = ($this->handler)($command);

    self::assertTrue($result->ok);
}
```

### Flow Tests

Flows are tested in the `myvars/form-flow` package's own suite. Individual bounded contexts do not need to re-test flow behavior; each context's own flow tests exercise the app's template overrides.
