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
| `ActionFlow` | Direct command execution (state changes) | GET or POST |
| `ConfirmFlow` | Confirm-then-act: delete & other confirmable actions (CSRF) | GET, POST |
| `SearchFlow` | Paginated index/list pages | GET |
| `InlineEditFlow` | Single-field inline editing via Turbo Frames | GET, POST |

All flows live in the external `myvars/form-flow` package (namespace `MyVars\FormFlow\`), installed at `vendor/myvars/form-flow/src/`. The app supplies the adapters (`Result`, `RedirectTarget`, `FlashMessenger`, `SearchCriteriaInterface`) and may override any of the package's default templates at `templates/shared/form_flow/*`.

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

### ActionFlow (State Transitions)

```
GET /order/{id}/allocate
  → Calls handler(Command) → Result
  → If Result.ok: flash success, redirect 303
  → If Result.fail: flash error, redirect 303
  → If Result.redirect: redirect to forced target
```

### ConfirmFlow (delete & confirmable actions)

```
GET /product/{id}/delete/confirm     (confirm())
  → Renders confirmation template
  → Returns 200

POST /product/{id}/delete            (execute())
  → Validates CSRF token (confirmKey + the command's id; confirmKey defaults to 'delete')
  → If invalid: flash error, redirect 303
  → If valid: delegates to ActionFlow.process()
```

Non-delete confirmable actions use the same flow via `FlowContext::forConfirm($model, key)`, which
namespaces the CSRF token to `key` so distinct actions on the same entity don't share a token. This
application has three: `cancel` (orders), `rewind` (purchase orders) and `remove` (supplier products).

### SearchFlow (Index/List)

```
GET /product/?page=1
  → Paginates the Pagerfanta adapter the controller passes in ($repository->findByCriteria($criteria))
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

// Irregular plural (the default plural is the display name + 's'):
FlowModel::create('demo', 'category', displayNamePlural: 'Categories')

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
FlowContext::forAction('app_order_show', ['id' => $id])
```

Fluent methods:
- `successRoute(string $route, array $params = [])` — Override redirect target (route + params)
- `template(string $template)` — Override template path
- `allowDelete(bool $allow)` — Show delete button on update forms

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
| `flowModelPlural` | `'Tasks'` | Plural display name (`FlowModel::plural()`) |

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
- `ConfirmFlow`: `result` (entity to confirm), `confirmKey` (CSRF token key)

## File Locations

```
vendor/myvars/form-flow/
├── config/services.php
├── templates/shared/form_flow/   # Design-neutral defaults (the app overrides at the same path)
└── src/
    ├── FormFlowBundle.php
    ├── FormFlow.php              # Create/update forms
    ├── ActionFlow.php            # Direct command execution (state transitions, actions)
    ├── ConfirmFlow.php           # Confirm-then-act (delete & confirmable actions)
    ├── SearchFlow.php            # Paginated lists
    ├── Concerns/RedirectsResponses.php
    ├── Contract/                 # ResultInterface, RedirectTargetInterface, FlasherInterface, SearchCriteriaInterface
    ├── Guard/AutoUpdateGuard.php # Auto-update submit detection
    ├── InlineEdit/               # InlineEditFlow, InlineEditContext, InlineFieldForm, InlineFieldType
    ├── Mapper/ObjectMapper.php
    ├── Redirect/                 # RedirectorInterface, TurboAwareRedirector (Turbo stream redirects)
    └── View/
        ├── FlowContext.php       # Flow configuration (forCreate, forUpdate, forDelete, forConfirm, forFilter, forSearch, forAction)
        ├── FlowModel.php         # Typed model value object (create, simple, withDisplayName, plural, template)
        ├── FlowRoutes.php        # Typed route name bag (fromPrefix, with)
        ├── FormOperation.php     # Operation enum (Create, Update, Delete, Filter, Action, Index)
        └── TemplateContext.php   # Template variable bag

src/Shared/                       # The app's adapters for the package's Contract/ ports
├── Application/Result.php                          # Handler result object
├── Application/RedirectTarget.php                  # Forced redirect target
├── Application/Search/SearchCriteriaInterface.php
└── UI/Http/FlashMessenger.php
```

## Related Documentation

- [Usage Patterns and Examples](Usage.md)
- [Template Overrides Cookbook](Overrides.md) - How to customize default templates
- [Embedded Forms](EmbeddedForms.md) - Inline forms within host pages
- [Turbo Integration](../Turbo/README.md) - How FormFlow uses Turbo for SPA-like responses
- [ADR 006: FormFlow Pattern](../../adr/006-formflow-controller-pattern.md) - Architecture decision
- [ADR 007: Turbo Architecture](../../adr/007-turbo-frame-modal-architecture.md) - Frame and modal design
