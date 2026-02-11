# Template Conventions

## Directory Structure

```
templates/
├── base.html.twig                         Main HTML layout (body frame, modal, flashes)
├── _header.html.twig                      Navigation bar
├── _footer.html.twig                      Footer
├── _logo.html.twig                        Logo partial
├── _menu.html.twig                        Sidebar navigation drawer
├── _flashes.html.twig                     Flash message rendering (Toast)
│
├── components/                            Twig Components (PascalCase)
│   ├── Card.html.twig
│   ├── Search.html.twig
│   └── ...
│
├── shared/
│   ├── form_flow/                         FormFlow framework templates
│   │   ├── base.html.twig                 Router — resolves operation to template
│   │   ├── create.html.twig               Default create form (modal)
│   │   ├── update.html.twig               Default update form (modal)
│   │   ├── delete.html.twig               Default delete confirmation (modal)
│   │   ├── index.html.twig                Default search/list page
│   │   ├── filter.html.twig               Default filter form (modal)
│   │   ├── missing.html.twig              Fallback when no template found
│   │   ├── inline_edit_display.html.twig  Inline edit: display mode
│   │   ├── inline_edit_form.html.twig     Inline edit: form mode
│   │   └── inline_edit_success.stream.html.twig
│   ├── turbo/                             Turbo integration
│   │   ├── modal_base.html.twig           Layout selector (modal vs full page)
│   │   ├── modal_frame.html.twig          Minimal modal wrapper
│   │   ├── embedded_frame.html.twig       Embedded turbo-frame layout
│   │   ├── _modal.html.twig               Dialog component in base layout
│   │   └── _frame_success_stream.html.twig
│   ├── email/                             Email base layout
│   └── pagerfanta/                        Pagination template
│
├── {context}/                             Bounded context pages & partials
│   ├── {entity}/
│   │   ├── index.html.twig               Search/list page
│   │   ├── show.html.twig                Detail page (composes card partials)
│   │   ├── _*_card.html.twig             Card partials
│   │   └── _inline_*.html.twig           Inline edit display partials
│   └── ...
│
└── bundles/                               Third-party template overrides
    ├── TwigBundle/Exception/              Error pages
    └── TalesFromADevFlowbiteBundle/       Form theme
```

## Naming Conventions

| Pattern | Purpose | Example |
|---|---|---|
| `index.html.twig` | Search/list page | `catalog/product/index.html.twig` |
| `show.html.twig` | Detail page | `order/show.html.twig` |
| `_*_card.html.twig` | Card partial (underscore = partial) | `_product_card.html.twig` |
| `_inline_*.html.twig` | Inline edit display partial | `_inline_name.html.twig` |
| `PascalCase.html.twig` | Twig Component | `Card.html.twig`, `Search.html.twig` |
| `*.stream.html.twig` | Turbo Stream template | `inline_edit_success.stream.html.twig` |
| Action templates | State transitions (modals) | `cancel.html.twig`, `reassign.html.twig` |

**Rules:**
- Underscore prefix (`_`) = included by other templates, never rendered directly.
- No prefix = routed page or flow template.
- PascalCase in `components/` = Twig Component consumed as `<twig:Name>`.
- Templates mirror bounded contexts: `{context}/{entity}/{action}.html.twig`.

## FormFlow Variables

Templates rendered by FormFlow receive these variables automatically:

| Variable | Type | Available in | Description |
|---|---|---|---|
| `flowModel` | `string` | All | Entity name (e.g., "Product", "Supplier") |
| `flowOperation` | `string` | All | Operation: create, update, delete, filter, search |
| `routes` | `FlowRoutes` | All | Typed route names (`.index`, `.new`, `.edit`, `.delete`, `.filter`, `.show`) |
| `template` | `string` | All | Resolved template path |
| `form` | `FormView` | create, update, filter | Symfony form view |
| `result` | Entity | update, delete, show | The entity being operated on |
| `results` | `Pagerfanta` | search (index) | Paginated result set |
| `flowBackLink` | `?string` | create, update | Optional back-to-list link URL |
| `flowAllowDelete` | `bool` | update | Whether to show delete button |

### Template Resolution Order

FormFlow resolves templates in this order:
1. Custom context template: `{context}/{entity}/{operation}.html.twig`
2. Shared base template: `shared/form_flow/{operation}.html.twig`
3. Fallback: `shared/form_flow/missing.html.twig`

To override the default create modal for products, create `catalog/product/create.html.twig`.

## Twig Component Blocks

### Search

| Block | Purpose | Required? |
|---|---|---|
| `result_count` | Header with result count | No (has default) |
| `sort` | Sort column headers (use `<twig:SortLink>`) | Yes |
| `list_item` | Single result card (loops over `results`) | Yes |
| `filter` | Filter icon/link | No (has default) |
| `add` | Create button | No (has default) |

### Card

| Block | Purpose |
|---|---|
| `content` | Main card body (implicit) |
| `cardImage` | Optional image slot (used with `layout="horizontal"`) |

### FlowForm

| Block | Purpose |
|---|---|
| `before_submit` | Content before the submit button (e.g., delete link) |

### Dialog / ConfirmDialog

| Block | Purpose |
|---|---|
| `content` | Dialog body content (implicit for Dialog) |
| `message` | Confirmation message text (ConfirmDialog) |

## Comment Header Style

Card partials and show pages should include a brief header comment:

```twig
{# Supplier product detail card.
   Used on: supplier product show page.
   Variable: supplierProduct (SupplierProduct entity) #}
```

Twig Components should document props above the `{% props %}` declaration:

```twig
{# Reusable card container with optional edit/show links and status highlight.
   - title: Optional heading above content
   - editLink: URL for floating edit button (opens modal)
   - showLink: URL wrapping content as clickable link
   - statusHighlight: Status string for left border colour #}
{% props title, editLink = null, showLink = null, statusHighlight = null %}
```

Keep headers minimal: entity name, where it's used, and variable contract. Don't repeat what's obvious from the template name.
