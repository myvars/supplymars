# FormFlow

How web controllers use the `myvars/form-flow` package: which flow fits which action, how handlers report back, and the rules the app adds. Read when writing or changing a web controller.

The flows live in the package (namespace `MyVars\FormFlow\`, `^1.4` in `composer.json`). Its full reference is in the tagged docs: [flows](https://github.com/myvars/form-flow/blob/v1.4.0/docs/flows.md), [templates](https://github.com/myvars/form-flow/blob/v1.4.0/docs/templates.md), [adapters](https://github.com/myvars/form-flow/blob/v1.4.0/docs/adapters.md). `vendor/myvars/form-flow` holds only the package `README.md`, because `docs/` is excluded from the installed package. Bump these links when `composer.json` moves past 1.4.

## Rules

| Flow | Use for | HTTP methods |
|---|---|---|
| `FormFlow` | Create, update and filter forms | GET, POST |
| `ActionFlow` | A state change with no form (allocate, lock, approve) | POST (the flow doesn't enforce it; route it that way) |
| `ConfirmFlow` | Confirm, then act (delete, cancel, rewind, remove), CSRF-checked | GET (`confirm()`), POST (`execute()`) |
| `SearchFlow` | Paginated index pages | GET |
| `InlineEditFlow` | One field edited in a Turbo Frame; see [InlineEdit](../InlineEdit/README.md) | GET, POST |

These rules are for flow controllers. The exceptions: `RegistrationController`, `ResetPasswordController` and `PlaygroundLoginController` call `addFlash()` themselves, and `ProductImageController` checks the upload and reorder tokens in the controller, calls `ActionFlow::process()` there without `->csrf()`, returns JSON and has no `model()` ([Security: CSRF](../../08-security.md#csrf)).

- **Controllers orchestrate; handlers decide.** A flow action is a single `return $flow->...(...)`. Flow controllers never set flash messages or build redirects. Handlers never touch `Request`, the flash bag, `Response` or templates; they return a `Result`. Non-flow pages such as `show` may query repositories and render directly (`CustomerController::show()`).
- **A state change is a POST with a CSRF token, never a GET link.** Use `->csrf($tokenId)` on the context; see [CSRF in flows](#csrf-in-flows). Why: [Security: CSRF](../../08-security.md#csrf).
- **Call flows with named arguments**, as every controller does.
- **Each flow controller has a private `model(): FlowModel` method**, not a class constant, because a constant cannot call the static factory `FlowModel::create('catalog', 'product')`.
- **Mappers are `__invoke` callables, form DTO → command**, in `{Context}/UI/Http/Form/Mapper/`, named `{Action}{Entity}Mapper`. They pass fields through (`CategoryFilterMapper`): no repository lookups (the handler resolves ids), no side effects. Validation is on the form model and the entity ([Security: Validation](../../08-security.md#validation)). The package's generic `MyVars\FormFlow\Mapper\ObjectMapper` exists but nothing here uses it.

### The app's adapters

The package defines ports in `MyVars\FormFlow\Contract\`; the app implements them. Symfony autowires them, and `config/services.yaml` aliases `FlasherInterface`.

| Port | App class |
|---|---|
| `ResultInterface` | `src/Shared/Application/Result.php` |
| `RedirectTargetInterface` | `src/Shared/Application/RedirectTarget.php` |
| `FlasherInterface` | `src/Shared/UI/Http/FlashMessenger.php` |
| `SearchCriteriaInterface` | `src/Shared/Application/Search/SearchCriteriaInterface.php` (extends the port) |

The package ships design-neutral templates. The app overrides them at the same path, `templates/shared/form_flow/*`; see [Overrides](Overrides.md).

### CSRF in flows

| Flow | What it checks |
|---|---|
| `ActionFlow` | The posted `_token` against the id given to `->csrf()`. **Without `->csrf()` it checks nothing.** Give the id in full (`'allocate' . $publicId`), so each action on each entity has its own token. |
| `ConfirmFlow::execute()` | Delegates to `ActionFlow` with the id `confirmKey . $command->id` (`confirmKey` is `'delete'` unless set with `forConfirm($model, $key)`). **The command needs a public `id`.** `DeleteMessage` has two ids, so it sets `$this->id` from the message id. |

A wrong or missing token flashes `Invalid CSRF token.` and redirects to the success route; the handler does not run.

## How to

### Pick the flow and context

`ProductController` (`src/Catalog/UI/Http/Controller/ProductController.php`) has index, filter, new, edit and delete. Copy the action from there, or from the controller named in the row.

| Action | Flow call | Context |
|---|---|---|
| `index` | `SearchFlow::search()` with `adapter: $repository->findByCriteria($criteria)` | `FlowContext::forSearch($model)` |
| `searchFilter` | `FormFlow::form()` with the criteria as `data` | `forFilter($model)` |
| `new` | `FormFlow::form()` | `forCreate($model)` |
| `edit` | `FormFlow::form()` with `ProductForm::fromEntity($product)` | `forUpdate($model)->allowDelete(true)` |
| `deleteConfirm` / `delete` | `ConfirmFlow::confirm()` / `execute()` | `forDelete($model)` |
| allocate (`OrderController`) | `ActionFlow::process()` | `forAction('app_order_show', [...])->csrf(...)` |
| cancel, rewind, remove (`OrderController`, `PurchaseOrderController`, `SupplierProductController`) | `ConfirmFlow::confirm()` / `execute()` | `forConfirm($model, 'rewind')->template(...)` |

Chainable methods on `FlowContext`:

| Method | Effect |
|---|---|
| `->template(string)` | Use this template instead of `{context}/{entity}/{operation}.html.twig` (ignored by `SearchFlow`) |
| `->successRoute(route, params)` | Redirect here on success (default: `routes.index`; `forAction()` takes it as its arguments) |
| `->allowDelete(bool)` | Sets `flowAllowDelete`; the app's update template then shows a delete link |
| `->csrf(string $tokenId)` | `ActionFlow` checks the posted `_token` against this id before calling the handler |

`FlowModel::create($context, $entity, displayName:, displayNamePlural:)` derives the template directory, the route names (`app_{context}_{entity}_{action}`, with `_search_filter` for the filter) and the default success route (`_index`). `FlowModel::simple('order')` drops the context (`app_order_*`, `templates/order/`). Override the label with `displayName: 'VAT Rate'` (`VatRateController`) or, for one action, `$this->model()->withDisplayName('Product Cost')` (`PricingController`). The plural defaults to the display name plus `s`. Template variables are listed in [Templates](../Templates/README.md#formflow-variables).

### A state change without a form

Allocate on the order page (`OrderController`, route `app_order_allocate`):

```php
context: FlowContext::forAction('app_order_show', ['id' => $order->getPublicId()->value()])
    ->csrf('allocate' . $order->getPublicId()->value()),
```

The trigger is a one-button form carrying the same token id:

```twig
{# templates/order/show.html.twig #}
<form method="post" action="{{ path('app_order_allocate', {id: result.publicId.value}) }}" data-turbo-frame="body">
    <input type="hidden" name="_token" value="{{ csrf_token('allocate' ~ result.publicId.value) }}">
    <button type="submit" class="... focus-ring">Allocate</button>
</form>
```

Use `<twig:Button type="submit">` when the trigger looks like a button, and a plain `<button>` with the link's classes plus `focus-ring` when it looks like a link or an icon. Use `ConfirmFlow` instead when the action should ask first.

### A confirmation that is not a delete

`forConfirm($model, $key)` scopes the CSRF token to `$key . $command->id`, so it never shares a token with delete (key `'delete'`). `SupplierProductController` has both `delete` and `remove`, which is why the key exists. Rewind (`PurchaseOrderController`):

```php
// GET  rewindConfirm: ->confirm(entity: $purchaseOrder, context: FlowContext::forConfirm($this->model(), 'rewind')
//          ->template('purchasing/purchase_order/rewind.html.twig'))
// POST rewind: ->execute(request: $request, command: new RewindPurchaseOrder($purchaseOrder->getPublicId()),
//          handler: $handler, context: FlowContext::forConfirm($this->model(), 'rewind')
//              ->successRoute('app_purchasing_purchase_order_show', ['id' => $purchaseOrder->getPublicId()->value()]))
```

The confirm template receives `confirmKey`; render the token as `csrfId="{{ confirmKey }}{{ result.publicId.value }}"`. Always pass `->template()` for the confirm page; see the gotcha in [Overrides](Overrides.md#gotchas).

### Report the outcome from a handler

```php
return Result::ok(
    message: 'Order created',
    payload: $order->getPublicId(),
    redirect: new RedirectTarget(route: 'app_order_show', params: ['id' => $order->getPublicId()->value()]),
);
```

From `CreateOrderHandler`. On failure a handler returns `Result::fail('Customer not found.')`. `payload` carries data for a non-HTTP caller (the API controller and `DemoOrderCreator` read the new id from it); the flows ignore it.

**Redirect precedence:** a handler's `RedirectTarget` beats the controller's `successRoute()`. Use the handler target when the destination depends on the outcome (the new record's page); use `successRoute()` for a destination known up front. Inside a Turbo Frame, what each kind of success returns (refresh or navigate) is in [Turbo](../Turbo/README.md).

| Scenario | Status |
|---|---|
| GET (form display, confirm page, index) | 200 |
| Valid POST, handler `ok` | 303 redirect |
| Valid POST, handler `fail` | 422, form re-rendered, error flash |
| Invalid POST (validation) | 422 |
| Any redirect inside a Turbo Frame | 200 with a Turbo Stream (`refresh` or `redirect`) |
| `ActionFlow` / `ConfirmFlow::execute()`, any outcome | 303 redirect |

**Flash keys** (`FlashMessenger`): `success()` → `success`, `warning()` → `warning`, `error()` → `danger`, rendered as `FlashToast`. The message comes from `Result::$message`; null means no flash. Only two flows add their own: `ActionFlow` (`Invalid CSRF token.`, also for `ConfirmFlow`, which delegates to it) and `SearchFlow` (`Page N not found.`, then a redirect to page 1 keeping the other query parameters).

## Gotchas

- **Auto-update forms** (a field change resubmits the form without saving): see [Submit on change](../UI/StimulusControllers.md#submit-on-change).
- **A record's id comes from the route, never from a form field.** The controller sets it on the form model (`ProductForm::fromEntity($product)`, `OrderItemForm::fromEntity($order)`) and the form type has no field for it, so a submit cannot change it and the mapper reads the route's id. A hidden id field would let a post act on a record other than the one `#[IsGranted]` checked. A post that adds the field anyway is refused with Symfony's "This form should not contain extra fields." (422).
- Flows are tested in the package's own suite. Test a context's controllers through HTTP (`tests/Order/UI/AllocateOrderFlowTest.php`) and its handlers directly (`tests/Order/Application/Handler/`); see [Testing](../../07-testing.md).

## Related

- [Overrides](Overrides.md): template resolution and how to replace a default template
- [Embedded Forms](EmbeddedForms.md): a FormFlow form inside another page
- [Turbo](../Turbo/README.md): layout detection, `formFrame`, refresh versus navigate after submit
- [Templates](../Templates/README.md): naming and template variables
- [ADR 006: FormFlow](../../adr/006-formflow-controller-pattern.md), [ADR 007: Turbo frames and modals](../../adr/007-turbo-frame-modal-architecture.md)
