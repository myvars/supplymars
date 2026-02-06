# Modal System

SupplyMars uses native `<dialog>` elements with Turbo Frames for modal dialogs. This provides accessible, keyboard-navigable modals without a JavaScript library.

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│  Link clicked: data-turbo-frame="modal"                         │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  Turbo fetches URL with header: turbo-frame: modal              │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  Server renders modal_base.html.twig                            │
│  → Detects header, extends modal_frame.html.twig                │
│  → Returns just <turbo-frame id="modal">...</turbo-frame>       │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  Turbo inserts content into <turbo-frame id="modal">            │
│  → turbo:frame-load event fires                                 │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  Stimulus controller: frameLoaded()                             │
│  → Calls dialog.showModal()                                     │
│  → Adds overflow-hidden to body                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Template Structure

### modal_base.html.twig

The key to the modal system - intelligently chooses layout:

```twig
{% extends app.request.headers.get('turbo-frame') == 'modal' ?
    'shared/turbo/modal_frame.html.twig' : 'base.html.twig' %}
```

**If `turbo-frame: modal` header present:**
- Extends `modal_frame.html.twig` (minimal layout)
- Returns only the frame content

**If no header (direct navigation):**
- Extends `base.html.twig` (full page)
- Form works as standalone page

### modal_frame.html.twig

Minimal wrapper for modal content:

```twig
<turbo-frame id="modal">
    {% block body %}{% endblock %}
</turbo-frame>

{{ include('shared/turbo/_frame_success_stream.html.twig', {frame: 'modal'}) }}
```

### _modal.html.twig

The modal component in `base.html.twig`:

```twig
<twig:Modal>
    <turbo-frame id="modal"
        data-basic-modal-target="frame"
        data-action="turbo:before-fetch-request->basic-modal#frameBusy
                     turbo:frame-render->basic-modal#frameIdle">
    </turbo-frame>
</twig:Modal>
```

### Modal.html.twig Component

Native dialog with Stimulus bindings:

```twig
<dialog class="..."
    data-controller="basic-modal"
    data-basic-modal-target="dialog"
    data-action="turbo:before-cache@window->basic-modal#close
                 turbo:submit-end->basic-modal#submitEnd
                 turbo:frame-load->basic-modal#frameLoaded
                 close->basic-modal#close
                 click->basic-modal#clickOutside">

    <div class="relative max-h-[calc(100vh-12rem)]">
        {{ block('content') }}
    </div>
</dialog>
```

## Stimulus Controller

### basic_modal_controller.js

**Targets:**

| Target | Element | Purpose |
|--------|---------|---------|
| `dialog` | `<dialog>` | Native dialog element |
| `frame` | `<turbo-frame>` | Content container |
| `loadingTemplate` | `<template>` | Loading spinner |

**Methods:**

| Method | Trigger | Action |
|--------|---------|--------|
| `open()` | Manual or frameLoaded | `dialog.showModal()`, body overflow |
| `close()` | submitEnd, click outside, escape | Clear frame, `dialog.close()` |
| `frameLoaded()` | `turbo:frame-load` | Auto-open if not already open |
| `submitEnd(event)` | `turbo:submit-end` | Close if `event.detail.success` |
| `frameBusy()` | `turbo:before-fetch-request` | Set loading state |
| `frameIdle()` | `turbo:frame-render` | Clear loading state |
| `clickOutside(event)` | Dialog click | Close if backdrop clicked |

**Key Implementation:**

```javascript
submitEnd(event) {
    // Only close on successful submission
    if (event.detail.success) {
        this.close();
    }
}

clickOutside(event) {
    // Close only if click was on backdrop, not dialog content
    if (event.target === this.dialogTarget) {
        this.close();
    }
}

close() {
    // Clear frame to reset state
    this.frameTarget.src = '';
    this.frameTarget.innerHTML = '';
    this.dialogTarget.close();
    document.body.classList.remove('overflow-hidden');
}
```

## Modal Form Flow

### Opening a Modal Form

1. User clicks link with `data-turbo-frame="modal"`:
   ```twig
   <a href="{{ path('app_product_edit', {id: product.publicId}) }}"
      data-turbo-frame="modal">
       Edit
   </a>
   ```

2. Turbo sends request with `turbo-frame: modal` header

3. Controller renders template extending `modal_base.html.twig`:
   ```twig
   {% extends 'shared/turbo/modal_base.html.twig' %}

   {% block body %}
       <twig:Dialog title="Edit Product">
           {{ form(form) }}
       </twig:Dialog>
   {% endblock %}
   ```

4. `frameLoaded()` opens the dialog

### Form Submission

1. Form submits with `data-turbo-frame="modal"` (auto-detected)

2. `frameBusy()` shows loading state (opacity reduction)

3. Server processes form:
   - **Invalid**: Re-renders form with errors, returns 422
   - **Valid**: Handler executes, returns Turbo Stream

4. On success:
   - Flash message appended via stream
   - `submitEnd()` receives `success: true`
   - Modal closes automatically
   - Page refreshes or navigates via stream

5. On validation error:
   - Form re-rendered in modal
   - `frameIdle()` clears loading state
   - User corrects and resubmits

### Form Context Detection

Submit buttons auto-detect modal context:

```twig
{# templates/shared/form_flow/_form.html.twig #}
<twig:Button
    type="submit"
    data-turbo-frame="{{ app.request.headers.get('turbo-frame') == 'modal' ? 'modal' : '_top' }}"
>
    {{ button_label|default('Save') }}
</twig:Button>
```

## Styling

### Dialog Styles

```css
dialog {
    /* Centered positioning */
    margin: auto;
    inset: 0;

    /* Sizing */
    width: 100%;
    max-width: 50%;
    max-height: 100%;

    /* Appearance */
    border-radius: 0.5rem;
    border: 1px solid theme('colors.gray.200');
    background: white;
    box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);

    /* Animation */
    animation: fade-in 0.15s ease-out;
}

/* Backdrop */
dialog::backdrop {
    background: rgb(17 24 39 / 0.6);
}

/* Only show when open */
dialog:not([open]) {
    display: none;
}
```

### Loading State

```css
/* Frame receives data-loading attribute */
turbo-frame[data-loading] {
    opacity: 0.3;
    pointer-events: none;
}

/* Loading spinner shown via peer selector */
.peer-data-[loading]:flex {
    display: flex;
}
```

## Best Practices

1. **Always use `modal_base.html.twig`** for modal-capable templates:
   ```twig
   {% extends 'shared/turbo/modal_base.html.twig' %}
   ```

2. **Use `<twig:Dialog>` component** for consistent styling:
   ```twig
   <twig:Dialog title="Delete Order" size="sm">
       {# content #}
   </twig:Dialog>
   ```

3. **Disable prefetch on state-changing links**:
   ```twig
   <a href="..." data-turbo-frame="modal" data-turbo-prefetch="false">
       Delete
   </a>
   ```

4. **Handle both modal and full-page** - Templates work in both contexts

5. **Don't nest modals** - One modal at a time; close before opening another

## Accessibility

Native `<dialog>` provides:

- **Focus trap**: Tab stays within dialog
- **Escape to close**: Built-in keyboard handling
- **ARIA role**: `dialog` role automatic
- **Background inert**: Content behind dialog is inaccessible

Additional considerations:

```twig
<twig:Dialog title="Edit Product">
    {# title becomes aria-labelledby automatically #}
</twig:Dialog>
```
