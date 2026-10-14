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
{% set _turbo_frame = app.request.headers.get('turbo-frame') %}
{% extends _turbo_frame == 'modal' ? 'shared/turbo/modal_frame.html.twig' : (_turbo_frame ? 'shared/turbo/embedded_frame.html.twig' : 'base.html.twig') %}
```

**If the `turbo-frame: modal` header is present:**
- Extends `modal_frame.html.twig` (minimal layout)
- Returns only the frame content

**If any other `turbo-frame` header is present:**
- Extends `embedded_frame.html.twig`, which wraps the body in a frame with that id (see [Embedded Forms](../FormFlow/EmbeddedForms.md))

**If no header (direct navigation):**
- Extends `base.html.twig` (full page)
- Form works as standalone page

### modal_frame.html.twig

Minimal wrapper for modal content:

```twig
<turbo-frame id="modal">
    {% block body %}{% endblock %}
    {{ include ('shared/turbo/_frame_success_stream.html.twig', { frame: 'modal' }) }}
</turbo-frame>
```

### _modal.html.twig

The modal component in `base.html.twig`:

```twig
<twig:Modal :closeButton="false">
    <div class="relative">
        <turbo-frame
            id="modal"
            data-basic-modal-target="frame"
            data-action="turbo:before-fetch-request->basic-modal#frameBusy turbo:frame-render->basic-modal#frameIdle"
            class="peer block data-[loading]:opacity-30"
        >
            {{ include ('shared/turbo/_frame_success_stream.html.twig', { frame: 'modal' }) }}
        </turbo-frame>
        <div class="pointer-events-none hidden peer-data-[loading]:flex absolute inset-0 items-center justify-center">
            <twig:Spinner class="size-8 text-gray-400"/>
        </div>
    </div>
</twig:Modal>
```

### Modal.html.twig Component

Native dialog with Stimulus bindings. The controller sits on a wrapping `<div>`; the `<dialog>` is one of its targets. Props: `closeButton` (false), `padding` (`p-5`), `fixedTop` (false).

```twig
<div data-controller="basic-modal"
     data-action="turbo:before-cache@window->basic-modal#close
                  turbo:submit-end->basic-modal#submitEnd
                  turbo:frame-load->basic-modal#frameLoaded">

    <dialog class="..."
        data-basic-modal-target="dialog"
        data-modal-size="md"
        aria-labelledby="modal-title"
        data-action="close->basic-modal#close
                     mousedown->basic-modal#onMouseDown
                     click->basic-modal#clickOutside">

        <div class="grow overflow-auto p-1">
            {% block content %}{% endblock %}
        </div>
    </dialog>
</div>
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
| `close()` | `close` event (Escape, backdrop click, close buttons), `turbo:before-cache`, successful submit | `dialog.close()`, reset size to `md`, clear the frame and its `src`, restore body scroll |
| `frameLoaded()` | `turbo:frame-load` | Read content size, auto-open if not already open |
| `submitEnd(event)` | `turbo:submit-end` | Close if `event.detail.success` |
| `frameBusy()` | `turbo:before-fetch-request` | Set loading state |
| `frameIdle()` | `turbo:frame-render` | Clear loading state |
| `onMouseDown(event)` | Dialog `mousedown` | Record where the press started |
| `clickOutside(event)` | Dialog click | Close if the press both started and ended on the backdrop |
| `showLoading()` | Manual | Copy the `loadingTemplate` into the frame while the dialog is closed |

**Key Implementation:**

```javascript
submitEnd(event) {
    // Only close on successful submission
    if (event.detail.success) {
        this.close();
    }
}

clickOutside(event) {
    // Only close if BOTH mousedown and mouseup were on the dialog backdrop,
    // so a text selection dragged out of the dialog does not close it
    if (event.target !== this.dialogTarget || this.mouseDownTarget !== this.dialogTarget) {
        this.mouseDownTarget = null;
        return;
    }
    if (!this.#isClickInElement(event, this.dialogTarget)) {
        this.dialogTarget.close();
    }
    this.mouseDownTarget = null;
}

close() {
    if (this.hasDialogTarget) {
        this.dialogTarget.close();
        this.dialogTarget.dataset.modalSize = 'md';
    }
    if (this.hasFrameTarget) {
        this.frameTarget.removeAttribute('src');
        this.frameTarget.innerHTML = '';
    }
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
       <twig:ModalPanel title="Edit Product">
           {{ form(form) }}
       </twig:ModalPanel>
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
{# templates/components/FlowForm.html.twig #}
<twig:Button
    type="submit"
    data-turbo-frame="{{ formFrame ?? app.request.headers.get('turbo-frame') ?: '_top' }}"
>
    {{ buttonLabel }}
</twig:Button>
```

## Styling

### Dialog Sizes

Size is controlled by a `data-modal-size` attribute on the `<dialog>`, driven by the loaded content. `ModalPanel.html.twig` exposes a `size` prop; `ConfirmDialog` defaults to `sm`.

| Size | CSS Class | Width | Use Case |
|------|-----------|-------|----------|
| `sm` | `md:max-w-md` | 28rem | Delete confirmations, simple actions |
| `md` | `md:min-w-[50%] md:max-w-[50%]` | 50% viewport | Standard forms (default) |
| `lg` | `md:max-w-2xl` | 42rem | Complex forms, multi-column |
| `xl` | `md:max-w-5xl` | 64rem | Wide content, data tables |

The Stimulus controller reads `data-modal-size` from the first matching element inside the turbo-frame in `frameLoaded()`, then sets it on the `<dialog>`. On close, it resets to `md`.

### Dialog Base Styles

The dialog is styled with Tailwind classes on the element in `Modal.html.twig`:

| Concern | Classes |
|---------|---------|
| Position | `m-auto inset-0` |
| Sizing | `w-full md:w-fit max-h-full`, plus the `data-[modal-size=*]` variants above |
| Appearance | `rounded-xl border border-border bg-popover shadow-2xl` |
| Animation | `animate-scale-in` |
| Backdrop | `backdrop:bg-gray-900/40 backdrop:backdrop-blur-[2px] dark:backdrop:bg-gray-950/50` |
| Visibility | `open:flex` (a closed `<dialog>` is hidden by the browser) |

### Loading State

```css
/* Frame receives data-loading attribute */
turbo-frame[data-loading] {
    opacity: 0.3;
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

2. **Use `<twig:ModalPanel>` component** for consistent styling:
   ```twig
   <twig:ModalPanel title="Delete Order" size="sm">
       {# content #}
   </twig:ModalPanel>
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
<twig:ModalPanel title="Edit Product">
    {# title becomes aria-labelledby automatically #}
</twig:ModalPanel>
```
