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

### FlowModel

Typed value object that replaces the raw `MODEL` string constant in controllers. Derives display name, template directory, routes, and default success route from convention-based factories:

```php
// Entity within a bounded context:
FlowModel::create('catalog', 'product')
FlowModel::create('purchasing', 'supplier_product')
FlowModel::create('pricing', 'vat_rate', displayName: 'VAT Rate')

// Entity without bounded context:
FlowModel::simple('customer')
FlowModel::simple('order_item')

// Override display name for specific actions:
$model->withDisplayName('Product Cost')
```

### FlowContext

Declarative configuration object for all flows. Created via factory methods that accept a `FlowModel`:

```php
$model = FlowModel::create('catalog', 'product');

FlowContext::forCreate($model)
FlowContext::forUpdate($model)
FlowContext::forDelete($model)
FlowContext::forFilter($model)
FlowContext::forSearch($model)
FlowContext::forSuccess('app_order_show', ['id' => $id])
```

Fluent methods:
- `successRoute(string $route, array $params = [])` — Override redirect target
- `successParams(array $params)` — Set route parameters
- `template(string $template)` — Override template path
- `allowDelete(bool $allow)` — Show delete button on update forms
- `redirectOptions(bool $refresh, int $status)` — Configure redirect behavior
- `routePrefix(string $prefix)` — Replace all derived route names from a new prefix

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
| `flowOperation` | `'create'` | Operation name |
| `template` | `'catalog/product/create.html.twig'` | Full template path |
| `routes` | `FlowRoutes` object | Typed route names (see below) |

The `routes` object (`FlowRoutes`) exposes named route properties instead of string concatenation:

| Property | Example | Description |
|----------|---------|-------------|
| `routes.index` | `'app_catalog_product_index'` | Index/list page |
| `routes.new` | `'app_catalog_product_new'` | Create form |
| `routes.show` | `'app_catalog_product_show'` | Detail page |
| `routes.delete` | `'app_catalog_product_delete'` | Delete action |
| `routes.deleteConfirm` | `'app_catalog_product_delete_confirm'` | Delete confirmation |
| `routes.filter` | `'app_catalog_product_search_filter'` | Search filter |

Usage in Twig:
```twig
{{ path(routes.new) }}
{{ path(routes.delete, {'id': result.publicId.value}) }}
```

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
├── Guard/
│   └── AutoUpdateGuard.php   # Auto-update submit detection
├── Redirect/
│   └── TurboAwareRedirector.php  # Turbo stream redirects
└── View/
    ├── FlowContext.php       # Flow configuration (forCreate, forUpdate, forDelete, forFilter, forSearch, forSuccess)
    ├── FlowModel.php         # Typed model value object (create, simple, withDisplayName, template)
    ├── FlowRoutes.php        # Typed route name bag (fromPrefix, with)
    ├── FormOperation.php     # Operation enum (Create, Update, Delete, Filter, Command, Index)
    └── TemplateContext.php   # Template variable bag

src/Shared/Application/
├── Result.php                # Handler result object
└── RedirectTarget.php        # Forced redirect target
```

## Related Documentation

- [Usage Patterns and Examples](Usage.md)
- [Turbo Integration](../Turbo/README.md) - How FormFlow uses Turbo for SPA-like responses
- [ADR 006: FormFlow Pattern](../../adr/006-formflow-controller-pattern.md) - Architecture decision
- [ADR 007: Turbo Architecture](../../adr/007-turbo-frame-modal-architecture.md) - Frame and modal design
