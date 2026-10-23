# Stimulus controllers

Every Stimulus controller in use in `assets/controllers/`, what it does and where it is mounted. Read when writing a new controller: one may already exist, and a kit recipe comes first ([ADR 012](../../adr/012-shadcn-ui-kit.md)). File `basic_modal_controller.js` is `data-controller="basic-modal"`.

## Controllers

"Kit" marks a controller that came with a Shadcn kit recipe (`ux:install`); its component attaches it, and you edit it like any project file but keep the local changes ADR 012 lists.

| Controller | What it does | Key values / targets | Used in |
|------------|--------------|----------------------|---------|
| `alert_dialog` | Kit. Confirmation dialog for content already on the page | targets `trigger`, `dialog` | `AlertDialog` component; no page uses it yet |
| `aos` | Animate On Scroll; loads `aos` lazily, skipped under reduced motion | none | `home/` pages (index, about, contact, privacy, terms) |
| `bar_chart` | Axis and tooltip formatting for a Chart.js bar chart; a click visits `linkUrl` + `?` + `options.linkParams[index]` | value `linkUrl` | `reporting/order_summary`, `product_sales`, `customer_insights` |
| `basic_modal` | The Turbo modal: opens on frame load, closes on a successful submit. While open, a live refresh leaves it and the page's scroll lock alone | targets `dialog`, `frame` | `Modal` component; see [Turbo](../Turbo/README.md) |
| `calendar` | Kit. Month grid for `Calendar` | targets `month`, `previous`, `next`, `input` | `Calendar`, via `DatePicker` |
| `card_link` | Makes a whole element navigate with Turbo `visit()`, ignoring clicks on inner links and buttons | value `url` | `purchasing/purchase_order/_order_po_card.html.twig` |
| `date_picker` | Kit. Input plus popover calendar | targets `trigger`, `value`, `input` | `DatePicker` (`DatePickerType`, see [Forms](Forms.md#pick-a-date-from-a-calendar)) |
| `dialog` | Kit. Opens and closes a modal `<dialog>`: Escape, click outside, focus return | targets `trigger`, `dialog` | `Sheet` (sidebar menu, help panel); `Dialog` component, no page uses it yet |
| `doughnut_chart` | Tooltip formatting for a Chart.js doughnut; a click replaces `{label}` in `linkUrl` with the segment's label | value `linkUrl` | `reporting/order_summary`, `customer_geographic`, `customer_segments` |
| `dropdown_menu` | Kit. Menu with arrow-key navigation, Escape, focus return | targets `trigger`, `content`, `item` | `DropdownMenu` (header user menu) |
| `dropdown_menu_sub` | Kit. Submenu of `dropdown_menu` | targets `trigger`, `content` | `DropdownMenu:Sub`; no submenu yet |
| `dropzone` | Drag-and-drop image upload (Dropzone.js), then a Turbo refresh | values `url`, `paramName` | `catalog/product_image/show.html.twig` |
| `help_drawer` | Loads the current page's help into the help `Sheet`; `?` shortcut | target `frame` | `_help_drawer.html.twig` |
| `inline_edit` | Focuses the field; Enter submits (Cmd/Ctrl+Enter in a textarea), Escape cancels, click outside submits; input grows to fit | targets `cancel`, `sizer` | `InlineEdit` component; see [InlineEdit](../InlineEdit/README.md) |
| `line_chart` | Axis and tooltip formatting for a Chart.js line chart | none | `audit/product_history.html.twig` |
| `live_open` | While a kit `Sheet` or `DropdownMenu` is open, marks its root `data-turbo-permanent`, so a live refresh keeps it open with what was loaded into it; never in the server's HTML | none | `_menu.html.twig`, `_help_drawer.html.twig`, `_header.html.twig` (user menu); see [Turbo](../Turbo/README.md#what-a-refresh-keeps) |
| `live_toaster` | Keeps the toaster as it is through a live refresh, then adds any new flash the refreshed page brings | action `keep` on `turbo:before-morph-element` | the wrapper round `<twig:Sonner id="flash-container">` in `base.html.twig` |
| `new_item_highlight` | One-shot glow (`.new-item-highlight`) on an item created less than `threshold` ms ago | values `createdAt` (ISO 8601), `threshold` (default 3000) | `order/index.html.twig` |
| `popover` | Kit. Floating content anchored to a trigger | targets `trigger`, `content` | `Popover`, `DatePicker` |
| `searchbox` | Debounced (250 ms) search input that reloads the results frame and replaces the URL | value `basePath`; target `queryInput` | `Search` component |
| `sidebar_active` | Highlights the current nav link (longest path prefix, query aware), opens the section holding it, toggles sections | `data-nav="top|section|dropdown|child|icon"` markers | `_menu.html.twig` |
| `sonner` | Kit. The page toaster: stacks toasts, pauses on hover, swipe to dismiss | targets `list`, `toast` | `<twig:Sonner id="flash-container">` in `base.html.twig`; `FlashToast` |
| `sortable` | Drag-and-drop reorder (SortableJS), posts the new order, then a Turbo refresh | values `reorderUrl`, `csrfToken` | `catalog/product_image/show.html.twig` |
| `submit_form` | `submitForm()` clicks the `submit` target | target `submit` | every `FlowForm`; see [Submit on change](#submit-on-change) |
| `theme_toggle` | Dark mode toggle, saved in `localStorage` | targets `sunIcon`, `moonIcon` | `_header.html.twig` |
| `toggle` | Shows and hides the `toggleable` target | target `toggleable` | `order/_order_item_card.html.twig` |
| `turnstile` | Renders the Cloudflare Turnstile widget for `TurnstileType` | value `sitekey`; targets `container`, `response` | `form/turnstile.html.twig` (registration), `customer/security/login.html.twig` |

`csrf_protection_controller.js` is not a Stimulus controller. It is Symfony's CSRF module, imported for its side effects: it sets the double-submit CSRF cookie on form submits, Turbo's included.

Shared helpers live in `assets/lib/`: `chart_format.js` (currency, percent and integer formatting for the chart controllers) and `turbo.js` (`turboRefresh()`, used by `dropzone` and `sortable`).

## How to

### Submit on change

`FlowForm` (`templates/components/FlowForm.html.twig`) mounts `submit_form`, so any `FlowForm` can submit itself when a field changes. Point the field's `change` event at `submitForm` and add a hidden `SubmitType` child named `auto-update` as the `submit` target; `hidden-submit-button` (`assets/styles/app.css`) hides it. From `ProductType`:

```php
->add('category', EntityType::class, [
    // …
    'attr' => ['data-action' => 'change->submit-form#submitForm'],
])
// …
$builder->add('auto-update', SubmitType::class, [
    'attr' => ['class' => 'hidden-submit-button', 'data-submit-form-target' => 'submit'],
]);
```

The name matters: `AutoUpdateGuard` in `myvars/form-flow` skips the handler only when the clicked button is a form child named `auto-update`, and FormFlow re-renders the form (422, errors cleared). Under any other name, or as a raw `<button name="auto-update">` in the template, every change is processed for real, so an edit form saves the record. supplymars does this on the field a dependent field (`addDependent()`, `symfonycasts/dynamic-forms`) hangs off, so the form re-renders with the new choices: `ProductType`, `ProductFilterType`, `SupplierProductType`, `SupplierProductFilterType`.

### Charts

Charts render through the `chart` controller of `@symfony/ux-chartjs` (`assets/controllers.json`) with `render_chart()`; do not import Chart.js in a controller. To add formatting or click-through, pass an in-house controller to `render_chart()` as `data-controller`. ux-chartjs mounts it next to its own `chart` controller on the `<canvas>`, where it listens for `chartjs:pre-connect` (to change options) and `chartjs:connect` (to get the chart). From `reporting/order_summary.html.twig`:

```twig
{{ render_chart(report.orderProgressChart, {
    'data-controller': 'doughnut_chart',
    'data-doughnut-chart-link-url-value': path('app_order_index') ~ '?orderStatus={label}&startDate=' ~ report.startDate ~ '&endDate=' ~ report.endDate,
}) }}
```

The chart builders (`src/Reporting/UI/Http/Chart/`, `src/Audit/UI/Http/Chart/`) set the axis type the formatters read and, for bar charts, `options.linkParams`.

### Side panels

The sidebar menu (`_menu.html.twig`) and the help panel (`_help_drawer.html.twig`) are kit `<twig:Sheet>` components: a modal `<dialog>` that slides in from an edge, driven by the `dialog` controller. The browser keeps focus inside and makes the page behind inert; a rule in `assets/styles/app.css` stops the page scrolling while one is open.

- Give the content an inner element that fills the panel. A click that lands on the `<dialog>` element itself counts as a click outside and closes it.
- `turbo:before-cache@window->dialog#close` on the `Sheet` closes it before Turbo caches the page, so a back navigation does not restore it open.
- Links inside close the panel as they navigate with `data-action="dialog#close"`.
- A panel that needs more behaviour puts its own controller on the same element and calls `dialog` (as `help_drawer` does).
- `live_open` sits on both panels, beside `dialog`, so a live refresh does not close an open one ([Turbo](../Turbo/README.md#what-a-refresh-keeps)). The kit's files stay as installed: it is added where the `Sheet` is used.

## Gotchas

- **Help panel fetches only when the page path changed.** `help_drawer` builds `/help?page=` from `window.location.pathname` (the query string is ignored) and sets the frame's `src` only when that URL differs from the last load, and the frame is `loading="lazy"`, so nothing is fetched until the panel opens. The `?` / `Shift+/` shortcut is ignored while typing in a field.
- **Doughnut links replace only `{label}`**, URL-encoded. There is no upper-case or enum-name placeholder, so the label must be the value the target filter expects.
- **Dropzone limits are a convenience.** `acceptedFiles` and `maxFilesize` (fixed in the controller) only stop the obvious mistakes in the browser; `CreateProductImageHandler` validates every upload again ([Security](../../08-security.md#file-uploads)). The controller sits on the upload `<form>`, so the form's CSRF `_token` field is posted with the files.

## Related

- [UI patterns](README.md)
- [Turbo](../Turbo/README.md): the modal (`basic_modal`) and the search frame
- [ADR 012](../../adr/012-shadcn-ui-kit.md): kit recipes and local changes
