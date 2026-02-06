# Turbo Pattern

This document describes how Hotwire Turbo is used in SupplyMars to provide SPA-like navigation without a JavaScript framework.

## Intent

Traditional server-rendered applications suffer from full-page reloads on every navigation. JavaScript SPAs solve this but require:

- Separate API layer
- Client-side state management
- Complex build tooling
- Duplicated validation logic

Turbo provides the UX benefits of an SPA while keeping server rendering:

- **Turbo Drive**: Intercepts links, fetches pages via AJAX, replaces `<body>`
- **Turbo Frames**: Scoped page updates (only part of the page changes)
- **Turbo Streams**: Server-pushed DOM mutations (append, replace, remove)

SupplyMars uses all three, with a structured frame hierarchy for consistent behavior.

## Frame Hierarchy

| Frame ID | Purpose | URL Update | Location |
|----------|---------|------------|----------|
| `body` | Main content area | Yes (`advance`) | `base.html.twig` |
| `modal` | Form dialogs | No | `_modal.html.twig` |
| `{model}-table` | Search/list results | Yes (`advance`) | `Search.html.twig` |
| `reports` | Dashboard widgets | No | Various dashboard templates |
| `url-refresh` | Hidden refresh trigger | No | `base.html.twig` |

## Quick Reference

### Link Targeting

```twig
{# Navigate to new page #}
<a href="{{ path('app_order_show', {id: order.publicId}) }}"
   data-turbo-frame="body">
    View Order
</a>

{# Open modal #}
<a href="{{ path('app_order_edit', {id: order.publicId}) }}"
   data-turbo-frame="modal">
    Edit Order
</a>

{# State-changing action (disable prefetch!) #}
<a href="{{ path('app_order_cancel_confirm', {id: order.publicId}) }}"
   data-turbo-frame="modal"
   data-turbo-prefetch="false">
    Cancel Order
</a>
```

### Form Submission

Forms automatically detect their context:

```twig
{# In shared/form_flow/_form.html.twig #}
<twig:Button
    type="submit"
    data-turbo-frame="{{ app.request.headers.get('turbo-frame') == 'modal' ? 'modal' : '_top' }}"
>
    Save
</twig:Button>
```

### Modal Templates

Templates that can be rendered as modals extend `modal_base.html.twig`:

```twig
{% extends 'shared/turbo/modal_base.html.twig' %}

{% block body %}
    <twig:Dialog title="Edit Order">
        {{ form(form) }}
    </twig:Dialog>
{% endblock %}
```

## File Locations

```
templates/
├── base.html.twig                    # Body frame, meta tags
└── shared/turbo/
    ├── modal_base.html.twig          # Layout decision (modal vs full)
    ├── modal_frame.html.twig         # Minimal modal layout
    ├── _modal.html.twig              # Modal component with <dialog>
    └── _frame_success_stream.html.twig  # Flash message stream

assets/controllers/
├── basic_modal_controller.js         # Modal lifecycle
├── searchbox_controller.js           # Live search
└── auto_url_updater_controller.js    # URL sync

src/Shared/UI/Http/FormFlow/Redirect/
└── TurboAwareRedirector.php          # Stream response generation (inline)
```

## Related Documentation

- [Frames](Frames.md) - Detailed frame documentation
- [Modals](Modals.md) - Modal system and Stimulus controller
- [Streams](Streams.md) - Turbo Stream patterns
- [ADR 007](../../adr/007-turbo-frame-modal-architecture.md) - Architecture decision
- [FormFlow](../FormFlow/README.md) - Controller pattern using Turbo
