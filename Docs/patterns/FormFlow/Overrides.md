# FormFlow Template Overrides

Recipes for replacing the template a flow renders. Read when an entity needs a page that differs from the shared default.

## Rules

`FormFlow`, `ConfirmFlow` and `SearchFlow` render `shared/form_flow/base.html.twig` (`ActionFlow` renders nothing; `InlineEditFlow` has its own three templates), which includes the first template that exists:

1. `template`: the path from `->template()`, or else `{context}/{entity}/{operation}.html.twig`
2. `shared/form_flow/{operation}.html.twig`
3. `shared/form_flow/missing.html.twig`

- The chain lives in the app's `templates/shared/form_flow/base.html.twig`, which overrides the package's identical default. Editing it changes resolution for every flow.
- The app's `templates/shared/form_flow/*` overrides the package default of the same name; the app's `templates/` wins.
- `->template()` replaces step 1 for `FormFlow` and `ConfirmFlow`. `SearchFlow` ignores it: it uses `{context}/{entity}/index.html.twig`, falling back to `shared/form_flow/index.html.twig`.
- Operations: `create`, `update`, `delete`, `filter`, `action`, `index`.
- Props and blocks of `ConfirmDialog`, `Search`, `FlowForm`, `ModalPanel` and `EntityCard` are in the header comment of each file in `templates/components/`. The kit's `Button` documents its props as `##` lines inside `{% props %}`.

## Recipes

### 1. Custom delete confirmation

Needed when the entity's label is not `name` (the shared `delete.html.twig` shows `result.name`), or the text should differ. Copy `templates/shared/form_flow/delete.html.twig` to `{context}/{entity}/delete.html.twig`: it extends `shared/turbo/modal_base.html.twig` and renders `<twig:ConfirmDialog>` (confirm button `destructive-solid` by default) with the text in the `message` block.

`csrfId` must equal what `ConfirmFlow::execute()` checks, `confirmKey . command id`: write `csrfId="{{ confirmKey|default('delete') }}{{ result.publicId.value }}"`.

### 2. Block a delete by state

Replace `ConfirmDialog` with a `<twig:ModalPanel>` that shows either a reason and a Close button, or the CSRF form. `templates/customer/delete.html.twig` refuses an admin user or a customer with orders:

```twig
{% if result.customerOrders.count > 0 %}
    <p class="mb-4 text-sm font-light text-red-500 dark:text-red-400">You cannot delete a customer with order history.</p>
    <twig:Button type="button" variant="outline" class="w-full" data-action="basic-modal#close">Close</twig:Button>
{% else %}
    <form method="post" action="{{ path('app_customer_delete', {'id': result.publicId.value}) }}">
        <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ result.publicId.value) }}">
        {# Cancel (variant="outline") + submit (variant="destructive-solid"), each class="flex-1" #}
    </form>
{% endif %}
```

The template is UX only; the handler must still refuse the delete.

### 3. Delete link on an update form

No template: `FlowContext::forUpdate($model)->allowDelete(true)`. The app's `shared/form_flow/update.html.twig` shows the link when `flowAllowDelete` is true (the package default does not).

### 4. Index page without filter or add button

`<twig:Search ... showFilter="{{ false }}">` drops the filter icon and its Turbo Stream (entities with no filter route: manufacturer, supplier, VAT rate, pool, customer). `showAdd="{{ false }}"` drops the create button (customer, purchase order). To change the button instead, override the `add` block, as `templates/order/index.html.twig` does (`<twig:Button as="a" variant="outline">` for Create Demo Order).

### 5. Confirmation for a non-delete action

Drive it with `ConfirmFlow` and `FlowContext::forConfirm($model, 'rewind')->template('purchasing/purchase_order/rewind.html.twig')` on the confirm route ([FormFlow](README.md#a-confirmation-that-is-not-a-delete)). The template is recipe 1 with `confirmVariant="warning"`, the variant for a confirmation that is not a delete. Live examples: `order/cancel.html.twig`, `purchasing/purchase_order/rewind.html.twig`, and `purchasing/supplier_product/remove.html.twig` (a hand-built form on `csrf_token(confirmKey ~ result.publicId.value)`).

### 6. Index page

An index built on `<twig:Search>` fills the `sort` and `list_item` blocks, one `<twig:SortLink>` per `SORT_OPTIONS` entry. Copy `templates/catalog/manufacturer/index.html.twig`.

## Gotchas

- **`forConfirm()` without `->template()` renders the fallback page.** It sets the operation to `action`, so the chain looks for `{context}/{entity}/action.html.twig`, then `shared/form_flow/action.html.twig` (which does not exist), then shows `missing.html.twig`. All three confirmations here pass `->template()`.
- An entity-specific template applies to every flow with that operation for the entity: `customer/delete.html.twig` is the customer delete page whatever controller renders it.
- `note/ticket/delete_message.html.twig` is rendered by the controller directly, not by `ConfirmFlow::confirm()`, so it has no `confirmKey` and writes the `'delete'` key itself.

## Related

- [FormFlow](README.md)
- [Templates](../Templates/README.md): naming and template variables
- [Embedded Forms](EmbeddedForms.md)
- Package [templates reference](https://github.com/myvars/form-flow/blob/v1.4.0/docs/templates.md)
