# Turbo Frames

Turbo Frames scope page updates to specific regions. When a link or form targets a frame, only that frame's content is replaced.

## Frame Definitions

### Body Frame

**Location:** `templates/base.html.twig`

```html
<turbo-frame id="body" data-turbo-action="advance" refresh="morph">
    {% block body %}{% endblock %}
</turbo-frame>
```

**Purpose:** Main content area for page-to-page navigation.

**Attributes:**
- `data-turbo-action="advance"` - Updates browser URL and adds history entry
- `refresh="morph"` - Uses morphing algorithm for smooth DOM updates

**Usage:**
```twig
<a href="{{ path('app_order_index') }}" data-turbo-frame="body">
    Orders
</a>
```

### Modal Frame

**Location:** `templates/shared/turbo/_modal.html.twig`

```html
<dialog data-controller="basic-modal" ...>
    <turbo-frame id="modal" data-basic-modal-target="frame">
        {# Content loaded here #}
    </turbo-frame>
</dialog>
```

**Purpose:** Overlay dialogs for forms, confirmations, and actions.

**Behavior:**
- Does not update URL (modal is temporary overlay)
- Wrapped in native `<dialog>` element
- Managed by `basic_modal` Stimulus controller
- Auto-opens when content loads
- Auto-closes on successful form submission

**Usage:**
```twig
<a href="{{ path('app_order_edit', {id: order.publicId}) }}"
   data-turbo-frame="modal">
    Edit
</a>
```

### Search Table Frame

**Location:** `templates/components/Search.html.twig`

```html
<turbo-frame id="{{ flowModel|slug }}-table"
             data-turbo-action="advance"
             refresh="morph">
    {# Paginated results #}
</turbo-frame>
```

**Purpose:** Search/list results with live filtering and pagination.

**Attributes:**
- Frame ID is dynamic: `product-table`, `order-table`, etc.
- `data-turbo-action="advance"` - URL reflects current filters/page
- `refresh="morph"` - Preserves scroll position during updates

**Usage:**
- Automatically targeted by searchbox controller on input
- Pagination links target this frame
- Filter form submissions target this frame

### Reports Frame

**Location:** Various dashboard templates

```html
<turbo-frame id="reports" refresh="morph">
    {# Dashboard widgets #}
</turbo-frame>
```

**Purpose:** Isolated dashboard sections that can refresh independently.

**Behavior:**
- Does not update URL
- Used for duration selectors (7d, 30d, etc.)
- Morphs content without full page reload

## Frame Targeting

### Target Attribute

Links and forms specify their target frame:

```twig
{# Explicit frame target #}
<a href="/orders" data-turbo-frame="body">Orders</a>

{# Form targeting #}
<form action="/search" data-turbo-frame="product-table">
```

### Special Targets

| Target | Behavior |
|--------|----------|
| `_top` | Full page navigation (bypass Turbo) |
| `_self` | Target the frame containing the element |
| (none) | Inherits from nearest ancestor frame |

### Target Priority

1. Explicit `data-turbo-frame` attribute on element
2. Inherited from ancestor `<turbo-frame>`
3. Default to `_top` (full page)

## Frame Loading States

### Busy Attribute

Frames receive `[busy]` attribute during fetch:

```css
turbo-frame[busy] {
    opacity: 0.5;
    pointer-events: none;
}
```

### Custom Loading State

The modal uses a custom `data-loading` attribute:

```javascript
// basic_modal_controller.js
frameBusy() {
    this.frameTarget.dataset.loading = true;
}

frameIdle() {
    delete this.frameTarget.dataset.loading;
}
```

```css
/* Styling based on loading state */
.peer-data-[loading]:opacity-30 { ... }
```

## Frame Events

| Event | When | Use Case |
|-------|------|----------|
| `turbo:before-fetch-request` | Before frame fetches | Show loading state |
| `turbo:frame-load` | After frame content loads | Auto-open modal |
| `turbo:frame-render` | After frame DOM updates | Hide loading state |

Example bindings:

```html
<turbo-frame id="modal"
    data-action="turbo:before-fetch-request->basic-modal#frameBusy
                 turbo:frame-render->basic-modal#frameIdle">
```

## URL Synchronization

### Advance vs Replace

```html
<!-- Adds history entry -->
<turbo-frame data-turbo-action="advance">

<!-- Replaces current history entry -->
<turbo-frame data-turbo-action="replace">
```

### Manual URL Update

For search results that need URL sync without frame navigation:

```javascript
// auto_url_updater_controller.js
updateUrl(url) {
    history.replaceState({}, '', url);
}
```

## Best Practices

1. **Always specify frame target** for links that should not navigate the full page

2. **Use `_top` for auth flows** - Login/logout should bypass Turbo:
   ```twig
   <a href="{{ path('app_logout') }}" data-turbo-frame="_top">Logout</a>
   ```

3. **Disable prefetch for state changes**:
   ```twig
   <a href="{{ path('app_order_cancel') }}"
      data-turbo-frame="modal"
      data-turbo-prefetch="false">
       Cancel
   </a>
   ```

4. **Match frame IDs** - Response must contain frame with matching ID:
   ```twig
   {# Request targets "modal", response must contain: #}
   <turbo-frame id="modal">
       {# content #}
   </turbo-frame>
   ```

5. **Use morphing for lists** - Prevents scroll jump on pagination:
   ```html
   <turbo-frame id="results" refresh="morph">
   ```
