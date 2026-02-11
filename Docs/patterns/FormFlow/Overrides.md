# Template Overrides Cookbook

This document covers how to customize the default FormFlow templates. Each recipe addresses a specific override scenario.

## How Template Resolution Works

`FormFlow` resolves templates by convention:

1. Custom template: `{context}/{entity}/{operation}.html.twig`
2. Base template: `shared/form_flow/{operation}.html.twig`
3. Fallback: `shared/form_flow/missing.html.twig`

If a context provides its own template (e.g. `catalog/product/create.html.twig`), it takes precedence. Otherwise the base template in `shared/form_flow/` is used.

You can also force a specific template via `FlowContext::template()`:

```php
context: FlowContext::forCreate(self::MODEL)
    ->template('catalog/product/my_custom.html.twig'),
```

## Recipes

### 1. Customize Delete Confirmation Text

Override `shared/form_flow/delete.html.twig` by creating a context-specific template that uses the `ConfirmDialog` component:

```twig
{# templates/catalog/product/delete.html.twig #}
{% extends 'shared/turbo/modal_base.html.twig' %}

{% block title %}Delete {{ flowModel }}{% endblock %}

{% block body %}
    <twig:ConfirmDialog
        title="Remove Product?"
        formAction="{{ path(routes.delete, {'id': result.publicId.value}) }}"
        csrfId="delete{{ result.publicId.value }}"
        confirmLabel="Yes, remove it"
        confirmVariant="danger"
        cancelLabel="Keep it"
    >
        <twig:block name="message">
            <p class="mb-2 text-base text-gray-500 dark:text-white">{{ result.name }}</p>
            <p class="mb-4 text-sm font-light text-gray-500 dark:text-gray-400">
                This will permanently remove the product and its pricing data.
            </p>
        </twig:block>
    </twig:ConfirmDialog>
{% endblock %}
```

**Props reference** (`ConfirmDialog`):

| Prop | Default | Description |
|------|---------|-------------|
| `title` | — | Dialog header text |
| `formAction` | — | POST target URL |
| `csrfId` | — | CSRF token ID (must match `DeleteFlow` convention: `'delete' . $command->id`) |
| `confirmLabel` | — | Submit button text |
| `confirmVariant` | `'danger'` | Button variant (`danger`, `warning`, `alternative`) |
| `cancelLabel` | `'Cancel'` | Cancel button text |

The `message` block accepts arbitrary HTML between the header and button row.

### 2. Conditional Delete Guard

When deletion should be blocked based on entity state, use a fully custom delete template instead of `ConfirmDialog`:

```twig
{# templates/customer/delete.html.twig #}
{% extends 'shared/turbo/modal_base.html.twig' %}

{% block title %}Delete {{ flowModel }}{% endblock %}

{% block body %}
    <twig:Dialog title="Delete Customer">
        {% if result.customerOrders.count > 0 %}
            <p class="mb-4 text-sm font-light text-red-500 dark:text-red-400">
                You cannot delete a customer with order history.
            </p>
            <twig:Button type="button" variant="alternative" class="w-full" data-action="basic-modal#close">
                Close
            </twig:Button>
        {% else %}
            <p class="mb-4 text-sm font-light text-gray-500 dark:text-gray-400">
                Are you sure you want to delete this {{ flowModel }}?
            </p>
            <form method="post" action="{{ path('app_customer_delete', {'id': result.publicId.value}) }}">
                <input type="hidden" name="_token" value="{{ csrf_token('delete'~result.publicId.value) }}">
                <div class="flex gap-3">
                    <twig:Button type="button" variant="alternative" class="w-full" data-action="basic-modal#close">
                        Cancel
                    </twig:Button>
                    <twig:Button type="submit" variant="danger" class="w-full">Delete Customer</twig:Button>
                </div>
            </form>
        {% endif %}
    </twig:Dialog>
{% endblock %}
```

### 3. Add a Delete Button to an Update Form

Set `allowDelete(true)` on the `FlowContext` in the controller:

```php
context: FlowContext::forUpdate(self::MODEL)->allowDelete(true),
```

The base `update.html.twig` checks `flowAllowDelete` and renders a delete link automatically:

```twig
{% if flowAllowDelete %}
    <hr class="my-6 h-px border-0 bg-gray-200 dark:bg-gray-700">
    <twig:Button
        tag="a"
        href="{{ path(routes.deleteConfirm, {'id': result.id}) }}"
        type="submit"
        variant="danger"
        class="w-full"
        data-turbo-prefetch="false">
        Delete
    </twig:Button>
{% endif %}
```

No template override needed — this is a controller-level toggle.

### 4. Hide the Filter Icon

Pass `showFilter="{{ false }}"` to the `Search` component:

```twig
<twig:Search
    flowModel="{{ flowModel }}"
    routes="{{ routes }}"
    results="{{ results }}"
    showFilter="{{ false }}"
>
```

This removes the filter icon from the search bar and suppresses the Turbo Stream that replaces it. Use this for entities that don't have a filter form.

### 5. Hide the Add Button

Pass `showAdd="{{ false }}"` to the `Search` component:

```twig
<twig:Search
    flowModel="{{ flowModel }}"
    routes="{{ routes }}"
    results="{{ results }}"
    showAdd="{{ false }}"
>
```

Use this when the entity has no create route (e.g. Customer, Purchase Order).

### 6. Custom Add Button

Override the `add` block in your index template:

```twig
<twig:Search
    flowModel="{{ flowModel }}"
    routes="{{ routes }}"
    results="{{ results }}"
>
    <twig:block name="add">
        <twig:Button tag="a" variant="primary" href="{{ path('app_catalog_product_import') }}">
            <twig:ux:icon name="bi:upload" class="h-4 w-4"/>
            <span>Import Products</span>
        </twig:Button>
    </twig:block>
    {# ... sort and list_item blocks #}
</twig:Search>
```

### 7. ConfirmDialog for Custom Confirmation Flows

Use `ConfirmDialog` for any state-transition that needs a confirmation step (not just deletes):

```twig
{# templates/purchasing/purchase_order/rewind.html.twig #}
{% extends 'shared/turbo/modal_base.html.twig' %}

{% block title %}Rewind Purchase Order{% endblock %}

{% block body %}
    <twig:ConfirmDialog
        title="Rewind Purchase Order"
        formAction="{{ path('app_purchasing_purchase_order_rewind', {'id': result.publicId.value}) }}"
        csrfId="delete{{ result.publicId.value }}"
        confirmLabel="Rewind to Pending"
        confirmVariant="warning"
    >
        <twig:block name="message">
            <p class="mb-4 text-sm font-light text-gray-500 dark:text-gray-400">
                Are you sure you want to rewind this Purchase Order to pending?
                This will reset all items and remove status history.
            </p>
        </twig:block>
    </twig:ConfirmDialog>
{% endblock %}
```

### 8. Custom Index Page

Every index template overrides at least `sort` and `list_item` blocks. This is the standard pattern:

```twig
{% extends 'base.html.twig' %}

{% block title %}{{ flowModel }} Search{% endblock %}

{% block body %}
    <twig:Search
        flowModel="{{ flowModel }}"
        routes="{{ routes }}"
        results="{{ results }}"
    >
        <twig:block name="sort">
            <twig:Card>
                <div class="flex justify-between overflow-auto">
                    <twig:SortLink sortValue="id">Id</twig:SortLink>
                    <twig:SortLink sortValue="name">Name</twig:SortLink>
                    <twig:SortLink sortValue="createdAt">Created</twig:SortLink>
                </div>
            </twig:Card>
        </twig:block>

        <twig:block name="list_item">
            <twig:Card showLink="{{ path(routes.show, {'id': result.publicId.value}) }}">
                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ result.name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ result.createdAt|date('jS M Y') }}</p>
            </twig:Card>
        </twig:block>
    </twig:Search>
{% endblock %}
```

**Available `Search` blocks:**

| Block | Purpose |
|-------|---------|
| `result_count` | Header with result count |
| `filter` | Filter icon inside search bar |
| `add` | Create button (default: "Create {{ flowModel }}") |
| `sort` | Sort column headers |
| `list_item` | Individual result card (loops over `results`) |

## Component Reference

| Component | Location | Purpose |
|-----------|----------|---------|
| `FlowForm` | `templates/components/FlowForm.html.twig` | Form rendering (fields + submit button) |
| `Search` | `templates/components/Search.html.twig` | Index page layout (search, sort, pagination) |
| `ConfirmDialog` | `templates/components/ConfirmDialog.html.twig` | Confirmation modal with CSRF form |
| `Dialog` | `templates/components/Dialog.html.twig` | Generic modal dialog shell |
| `Card` | `templates/components/Card.html.twig` | Card with optional edit/show links |
| `Button` | `templates/components/Button.html.twig` | Button or link-styled-as-button |
| `SortLink` | `templates/components/SortLink.html.twig` | Column sort header |
| `Pagination` | `templates/components/Pagination.html.twig` | Page navigation |
