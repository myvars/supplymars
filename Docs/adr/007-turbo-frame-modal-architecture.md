# ADR 007: Turbo Frame and Modal Architecture

## Status

Accepted

## Context

SupplyMars needed a responsive, SPA-like user experience without the complexity of a JavaScript framework (React, Vue, etc.). Key requirements:

- Fast navigation between pages
- Modal dialogs for create/edit/delete operations
- Live search with instant feedback
- Consistent behavior across all bounded contexts
- Minimal JavaScript maintenance burden

Traditional approaches had drawbacks:

| Approach | Drawback |
|----------|----------|
| Full page reloads | Slow, jarring UX |
| JavaScript SPA | High complexity, separate API layer |
| Livewire/Inertia | PHP-JS coupling, vendor lock-in |

## Decision

We adopted **Hotwire Turbo** with a structured frame hierarchy and native `<dialog>` modals.

### Frame Hierarchy

```
┌─────────────────────────────────────────────────────────────────┐
│  <turbo-frame id="body">                                        │
│  ├── Main content area                                          │
│  ├── data-turbo-action="advance" (updates browser URL)          │
│  └── refresh="morph" (smooth DOM updates)                       │
├─────────────────────────────────────────────────────────────────┤
│  <turbo-frame id="modal">                                       │
│  ├── Inside native <dialog> element                             │
│  ├── Managed by basic_modal Stimulus controller                 │
│  └── Does not update URL (overlay behavior)                     │
├─────────────────────────────────────────────────────────────────┤
│  <turbo-frame id="{model}-table">                               │
│  ├── Search/list results                                        │
│  ├── data-turbo-action="advance" (URL reflects filters)         │
│  └── Targeted by searchbox auto-submit                          │
├─────────────────────────────────────────────────────────────────┤
│  <turbo-frame id="reports">                                     │
│  ├── Dashboard widgets                                          │
│  └── refresh="morph" (isolated refresh capability)              │
└─────────────────────────────────────────────────────────────────┘
```

### Modal System

Rather than a JavaScript modal library, we use native `<dialog>` with Turbo:

1. **Opening**: Link with `data-turbo-frame="modal"` loads content into frame
2. **Layout Decision**: `modal_base.html.twig` detects `turbo-frame: modal` header and renders minimal layout
3. **Display**: Stimulus controller calls `dialog.showModal()` on frame load
4. **Submission**: Form submits to same frame, receives Turbo Stream response
5. **Closing**: Controller auto-closes on successful submission or user action

```twig
{# modal_base.html.twig - intelligent layout selection #}
{% extends app.request.headers.get('turbo-frame') == 'modal' ?
    'shared/turbo/modal_frame.html.twig' : 'base.html.twig' %}
```

This allows the same template to work both as a modal (when requested via Turbo) and as a full page (direct navigation or JS disabled).

### Turbo Stream Responses

Instead of HTTP redirects after form submission, we return Turbo Streams:

```html
<!-- Navigate to different page -->
<turbo-stream action="redirect" url="/order/abc123"></turbo-stream>

<!-- Refresh current page in place -->
<turbo-stream action="refresh"></turbo-stream>

<!-- Append flash message -->
<turbo-stream action="append" target="flash-container">
    <template><!-- flash HTML --></template>
</turbo-stream>
```

The `TurboAwareRedirector` detects Turbo requests and returns streams instead of HTTP 303 redirects.

### Link Targeting Rules

| Context | Attribute | Frame |
|---------|-----------|-------|
| Page navigation | `data-turbo-frame="body"` | Main content |
| Forms/actions | `data-turbo-frame="modal"` | Modal overlay |
| Search results | `data-turbo-frame="{model}-table"` | List area |
| Auth/full reload | `data-turbo-frame="_top"` | Bypass Turbo |

**Critical rule**: State-changing links must include `data-turbo-prefetch="false"` to prevent accidental prefetch side effects.

### Form Frame Detection

Forms automatically target the correct frame based on request context:

```twig
{# Submit button in _form.html.twig #}
data-turbo-frame="{{ app.request.headers.get('turbo-frame') == 'modal' ? 'modal' : '_top' }}"
```

## Consequences

### Positive

- **SPA-like UX**: Instant navigation, no full page reloads
- **Progressive enhancement**: Works without JavaScript (falls back to full pages)
- **Minimal JS**: One Stimulus controller for modals, not a framework
- **Consistent patterns**: All CRUD operations behave identically
- **Server-rendered**: No API duplication, forms work naturally
- **Native accessibility**: `<dialog>` has built-in focus management

### Negative

- **Learning curve**: Developers must understand frame targeting
- **Debugging complexity**: Network tab shows HTML fragments, not full pages
- **Header dependency**: Modal detection relies on `turbo-frame` header
- **Limited offline**: No service worker integration

### Implementation Notes

Key files:

| File | Purpose |
|------|---------|
| `assets/controllers/basic_modal_controller.js` | Modal lifecycle management |
| `templates/base.html.twig` | Body frame and Turbo meta tags |
| `templates/shared/turbo/modal_base.html.twig` | Layout decision logic |
| `templates/shared/turbo/_modal.html.twig` | Modal component with dialog |
| `templates/shared/turbo/_frame_success_stream.html.twig` | Flash message streams |
| `src/Shared/UI/Http/FormFlow/Redirect/TurboAwareRedirector.php` | Stream response generation (inline) |

Turbo configuration in `base.html.twig`:

```html
<meta name="turbo-view-transition" content="true">
<meta name="turbo-refresh-method" content="replace">
<meta name="turbo-refresh-scroll" content="preserve">
```

These meta tags are disabled in test environment to ensure predictable test behavior.

## Related Documentation

- [Turbo Frames Pattern](../patterns/Turbo/Frames.md)
- [Modal System Pattern](../patterns/Turbo/Modals.md)
- [Turbo Streams Pattern](../patterns/Turbo/Streams.md)
- [FormFlow Pattern](../patterns/FormFlow/README.md) - Uses TurboAwareRedirector
