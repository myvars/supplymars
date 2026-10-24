# Template Conventions

How templates under `templates/` are named and documented, and the variables FormFlow passes to them. Read it before adding a template.

## Naming

| Pattern | Purpose | Example |
|---|---|---|
| `{context}/{entity}/index.html.twig` | Search/list page | `catalog/product/index.html.twig` |
| `{context}/{entity}/show.html.twig` | Detail page, composed of card partials | `catalog/product/show.html.twig`, `order/show.html.twig` |
| `{context}/{entity}/{action}.html.twig` | Flow template or action modal | `order/cancel.html.twig`, `note/ticket/reassign.html.twig`, `customer/delete.html.twig` |
| `_*_card.html.twig` | Card partial | `catalog/product/_product_card.html.twig` |
| `_inline_*.html.twig` | Inline edit display partial | `catalog/product/_inline_name.html.twig` |
| `components/PascalCase.html.twig` | Twig Component, used as `<twig:Name>` | `EntityCard.html.twig`, `Search.html.twig` |
| `*.stream.html.twig` | Turbo Stream template | `shared/form_flow/inline_edit_success.stream.html.twig` |

- An underscore prefix means a partial: included by other templates, never rendered directly. No prefix means a routed page or flow template.
- Templates mirror the bounded contexts: `{context}/{entity}/...`, or `{entity}/...` for a `FlowModel::simple()` controller (`order/`, `customer/`).
- `shared/form_flow/` holds the app's overrides of the `myvars/form-flow` default templates. Which one a flow renders: [Overrides](../FormFlow/Overrides.md).
- `shared/turbo/` holds the modal and frame layouts: [Turbo](../Turbo/README.md).
- `bundles/TwigBundle/Exception/` holds the error pages (`error.html.twig`, `error403`, `error404`, `error500`). Each fills the `code`, `heading`, `message` and optional `note` blocks of `_layout.html.twig`, which renders them as a full page, or as a `ModalPanel` when the request came from the `modal` frame ([Turbo](../Turbo/README.md#gotchas)).
- Help topics in `help/` extend `help/_layout.html.twig`.

## FormFlow variables

| Variable | Type | Available in | Description |
|---|---|---|---|
| `flowModel` | `string` | All | Display name ("Product", "VAT Rate") |
| `flowModelPlural` | `string` | All | Plural display name (display name + "s" unless `displayNamePlural:` is set); no app template uses it yet |
| `flowOperation` | `string` | All | `create`, `update`, `delete`, `filter`, `action`, `index` |
| `template` | `string` | All | First template the include chain tries |
| `routes` | `FlowRoutes` | All | Route names: `.index`, `.new`, `.show`, `.delete`, `.deleteConfirm`, `.filter` |
| `form` | `FormView` | create, update, filter | The form |
| `result` | object | create, update, filter, delete, action | The form data object (form flows); the entity (confirm pages) |
| `flowBackLink` | `?string` | create, update, filter | The Referer URL, or null |
| `flowAllowDelete` | `bool` | create, update, filter | Set by `->allowDelete()`; the app's update template uses it |
| `results` | `Pagerfanta` | index | Paginated result set |
| `confirmKey` | `string` | delete, action (confirm page) | CSRF token key (`delete` unless set by `forConfirm()`) |

Use `routes` rather than building route names: `{{ path(routes.delete, {'id': result.publicId.value}) }}`.

## Comment headers

New card partials, show pages and action modals start with a short comment: what it is, where it is used, and its variables. Not every existing one has it yet.

```twig
{# Single order item card with product image, price, quantity, and supplier toggle.
   Used on: order show page (inside _order_items_card). Variable: orderItem (CustomerOrderItem) #}
```

A Twig Component documents its props and blocks in the same header, above `{% props %}` for an anonymous component. A PHP-backed component (`EntityCard`) has no `{% props %}` and lists its props in the header. That header is the reference for the component's props and blocks; do not copy it into docs.

## Gotchas

- `Search`'s `sort` and `list_item` blocks are empty by default, so an index page that does not fill them shows no sort links and an empty item per result. The shared `shared/form_flow/index.html.twig` fills both with the id only.
- After `ux:install` replaces a template at an existing path, run `rm -rf var/cache/dev var/cache/test` (see [ADR 012](../../adr/012-shadcn-ui-kit.md)).

## Related

- [FormFlow Overrides](../FormFlow/Overrides.md): template resolution and recipes
- [FormFlow](../FormFlow/README.md)
- Package [templates reference](https://github.com/myvars/form-flow/blob/v1.4.0/docs/templates.md)
