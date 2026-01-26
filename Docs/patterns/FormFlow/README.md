# FormFlow Pattern

This document describes the FormFlow pattern used in SupplyMars to keep controllers thin and provide consistent HTTP handling across all bounded contexts.

## Intent

Controllers in a Symfony application tend to accumulate repetitive boilerplate:
- Form creation and handling
- Validation and error display
- Flash messages
- Redirects
- CSRF validation (for deletes)
- Pagination and out-of-range handling

The FormFlow pattern extracts this boilerplate into reusable Flow classes, leaving controllers as simple coordinators that:
1. Inject dependencies
2. Configure the flow via `FlowContext`
3. Return the flow's response

This achieves:
- **Thin controllers** — 5-15 lines per action
- **Consistent UX** — All forms behave identically
- **Turbo integration** — Automatic Turbo stream support
- **Testability** — Flows are tested once; controllers test routing/auth

## Flow Classes

| Class | Purpose | HTTP Methods |
|-------|---------|--------------|
| `FormFlow` | Create/update forms with validation | GET, POST |
| `CommandFlow` | Direct command execution (state changes) | GET or POST |
| `DeleteFlow` | Delete confirmation + CSRF-validated delete | GET, POST |
| `SearchFlow` | Paginated index/list pages | GET |
| `ShowFlow` | Read-only detail pages | GET |

All flows are located in `src/Shared/UI/Http/FormFlow/`.

## HTTP Lifecycle

### FormFlow (Create/Update)

```
GET /product/new
  → Creates empty form
  → Renders template with form
  → Returns 200

POST /product/new (valid data)
  → Binds form data
  → Validates form
  → Calls mapper(formData) → Command
  → Calls handler(Command) → Result
  → If Result.ok: flash success, redirect 303
  → If Result.fail: flash error, return 422

POST /product/new (invalid data)
  → Binds form data
  → Validates form (fails)
  → Re-renders template with errors
  → Returns 422
```

### CommandFlow (State Transitions)

```
GET /order/{id}/allocate
  → Calls handler(Command) → Result
  → If Result.ok: flash success, redirect 303
  → If Result.fail: flash error, redirect 303
  → If Result.redirect: redirect to forced target
```

### DeleteFlow

```
GET /product/{id}/delete/confirm
  → Renders confirmation template
  → Returns 200

POST /product/{id}/delete
  → Validates CSRF token ('delete' + entity.publicId)
  → If invalid: flash error, redirect 303
  → If valid: delegates to CommandFlow.process()
```

### SearchFlow (Index/List)

```
GET /product/?page=1
  → Calls repository.findByCriteria(criteria)
  → Returns 200 with paginated results

GET /product/?page=999 (out of range)
  → Catches OutOfRangeCurrentPageException
  → Flashes warning "Page 999 not found"
  → Redirects to page 1 (preserves other params)
```

## API Reference

### FlowContext

Declarative configuration object for all flows. Created via factory methods:

```php
FlowContext::forCreate('catalog/product')
FlowContext::forUpdate('catalog/product')
FlowContext::forDelete('catalog/product')
FlowContext::forFilter('catalog/product')
FlowContext::forSearch('catalog/product')
FlowContext::forSuccess('app_order_show', ['id' => $id])
```

Fluent methods:
- `successRoute(string $route, array $params = [])` — Override redirect target
- `successParams(array $params)` — Set route parameters
- `template(string $template)` — Override template path
- `allowDelete(bool $allow)` — Show delete button on update forms
- `redirectOptions(bool $refresh, int $status)` — Configure redirect behavior

### Result

Return type for all command handlers:

```php
Result::ok(?string $message = null, mixed $payload = null, ?RedirectTarget $redirect = null)
Result::fail(?string $message = null, mixed $payload = null)
```

Properties:
- `$ok` — Boolean success indicator
- `$message` — Flash message text
- `$payload` — Optional data (rarely used)
- `$redirect` — Optional forced redirect target

### RedirectTarget

Used when a handler needs to override the default success URL:

```php
new RedirectTarget(
    route: 'app_order_show',
    params: ['id' => $order->getPublicId()->value()],
    redirectRefresh: false,
    redirectStatus: 303,
)
```

## Template Variables

All templates receive these variables from `TemplateContext`:

| Variable | Example | Description |
|----------|---------|-------------|
| `flowModel` | `'Product'` | Capitalized model name |
| `flowRoute` | `'catalog_product'` | Snake-cased route segment |
| `flowPath` | `'catalog/product/'` | Template path segment |
| `flowOperation` | `'create'` | Operation name |
| `template` | `'catalog/product/create.html.twig'` | Full template path |

Additional variables per flow:
- `FormFlow`: `form`, `result`, `flowBackLink`, `flowAllowDelete`
- `SearchFlow`: `results` (pagination object)
- `DeleteFlow`: `result` (entity to delete)

## File Locations

```
src/Shared/UI/Http/FormFlow/
├── FormFlow.php              # Create/update forms
├── CommandFlow.php           # Direct command execution
├── DeleteFlow.php            # Delete confirmation
├── SearchFlow.php            # Paginated lists
├── ShowFlow.php              # Detail pages
├── Guard/
│   └── AutoUpdateGuard.php   # Auto-update submit detection
├── Redirect/
│   └── TurboAwareRedirector.php  # Turbo stream redirects
└── View/
    ├── FlowContext.php       # Flow configuration (forCreate, forUpdate, forDelete, forFilter, forSearch, forSuccess)
    ├── FormOperation.php     # Operation enum (Create, Update, Delete, Filter, Command, Index)
    ├── ModelPath.php         # Path/route computation
    └── TemplateContext.php   # Template variable bag

src/Shared/Application/
├── Result.php                # Handler result object
└── RedirectTarget.php        # Forced redirect target
```

## Related Documentation

- [Usage Patterns and Examples](Usage.md)
