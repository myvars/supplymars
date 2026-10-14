# ADR 006: FormFlow Controller Pattern

## Status

Accepted

## Context

Symfony controllers often become bloated with repetitive code:

- Form creation and handling
- Validation error handling
- Flash message management
- Redirect logic
- Turbo/AJAX response handling

Each CRUD operation repeated similar patterns with slight variations, leading to:
- Code duplication across controllers
- Inconsistent user experience
- Difficult testing of controller logic

## Decision

We implemented a **FormFlow abstraction layer** that standardizes controller behavior:

### Flow Types

| Flow | Purpose | Key Method |
|------|---------|------------|
| `FormFlow` | Create/update with Symfony forms | `form()` |
| `ActionFlow` | Direct command execution (no form) | `process()` |
| `ConfirmFlow` | Confirm-then-act (delete & other confirmable actions) | `confirm()`, `execute()` |
| `SearchFlow` | Paginated index pages | `search()` |
| `InlineEditFlow` | Inline field editing via Turbo Frames | `handleField()` |

### Controller Usage

Each controller defines a typed `FlowModel` via a private method:

```php
private function model(): FlowModel
{
    return FlowModel::create('catalog', 'manufacturer');
}

#[Route(path: '/manufacturer/new', name: 'app_catalog_manufacturer_new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    CreateManufacturerMapper $mapper,
    CreateManufacturerHandler $handler,
    FormFlow $flow,
): Response {
    return $flow->form(
        request: $request,
        formType: ManufacturerType::class,
        data: new ManufacturerForm(),
        mapper: $mapper,
        handler: $handler,
        context: FlowContext::forCreate($this->model()),
    );
}
```

### Flow Internals

```
GET Request:
    → Render form template

POST Request:
    → Submit form
    → If invalid: re-render with errors
    → Map form data to Command
    → Execute Handler
    → If handler fails: re-render with error message
    → Flash success message
    → Redirect to success URL
```

### FlowContext Configuration

```php
$model = FlowModel::create('catalog', 'manufacturer');

FlowContext::forCreate($model)
    ->successRoute('app_catalog_manufacturer_index')
    ->template('catalog/manufacturer/create.html.twig')
```

### Turbo Integration

The `TurboAwareRedirector` detects Turbo requests and returns appropriate responses:
- Regular request: HTTP 303 redirect
- Turbo request: Turbo Stream refresh or redirect

## Consequences

### Positive

- **DRY controllers**: 5-10 line controller methods instead of 30-50
- **Consistent UX**: All forms behave identically
- **Turbo support**: Automatic SPA-like behavior
- **Testable**: Flow logic can be unit tested
- **Standardized flash messages**: Success/error patterns uniform

### Negative

- **Learning curve**: Developers must understand Flow abstraction
- **Magic**: Behavior hidden in Flow classes
- **Inflexibility**: Non-standard flows require workarounds
- **Debugging**: Stack traces go through Flow classes

### Implementation Notes

Key files:
- `vendor/myvars/form-flow/src/FormFlow.php` - Main form handling
- `vendor/myvars/form-flow/src/View/FlowContext.php` - Configuration
- `vendor/myvars/form-flow/src/Redirect/TurboAwareRedirector.php` - Response handling

The pattern separates concerns:
- **Controller**: Wires dependencies, delegates to Flow
- **Flow**: Handles HTTP lifecycle
- **Mapper**: Transforms form DTO → Command
- **Handler**: Executes business logic, returns Result
- **FlowContext**: Configures routes, templates, messages

This allows each component to be tested independently and reused across different operations.

### Naming Convention

Routes follow: `app_{context}_{entity}_{action}`
Templates follow: `{context}/{entity}/{operation}.html.twig` (operation = `create`, `update`, `delete`, `filter`, `index`), falling back to `shared/form_flow/{operation}.html.twig`

Route names are exposed to templates via a typed `FlowRoutes` object (derived automatically from the `FlowModel`):

```php
// FlowModel derives routes, templates, and display name from convention:
$model = FlowModel::create('catalog', 'manufacturer');
$model->routes->index;   // 'app_catalog_manufacturer_index'
$model->routes->new;     // 'app_catalog_manufacturer_new'
$model->routes->delete;  // 'app_catalog_manufacturer_delete'
$model->displayName;     // 'Manufacturer'
$model->template('create'); // 'catalog/manufacturer/create.html.twig'
```

```twig
{# In Twig — use routes.* properties directly: #}
{{ path(routes.new) }}
{{ path(routes.delete, {'id': result.publicId.value}) }}
```
