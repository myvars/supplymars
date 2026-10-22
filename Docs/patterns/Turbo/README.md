# Turbo frames, modals and streams

How pages, modals and form submits use Turbo frames and streams. Read when adding a link, a modal form or a frame, or when a flash or modal misbehaves.

## Rules

### Frames

| Frame id | Purpose | Defined in |
|----------|---------|------------|
| `body` | Main content; `data-turbo-action="advance"`, `refresh="morph"` | `templates/base.html.twig` |
| `modal` | The one modal dialog | `templates/shared/turbo/_modal.html.twig` (inside `<twig:Modal>`) |
| `{model}-table` | Search results and pagination; `advance`, `refresh="morph"`. The id is `{{ flowModel\|slug }}-table` | `templates/components/Search.html.twig` |
| `reports` | Reporting dashboards; the duration links swap only this frame. Also lazy-loaded with `src` into show pages (product sales, category, subcategory, manufacturer, supplier) | `templates/reporting/*.html.twig`, `templates/reporting/_dashboard_card.html.twig` |
| `help` | Contextual help panel (lazy) | `templates/_help_drawer.html.twig`; every help template extends `templates/help/_layout.html.twig`, which renders the frame |
| `inline-edit-{entity}-{id}-{field}` | One inline-editable field | `templates/components/InlineEdit.html.twig` — see [InlineEdit](../InlineEdit/README.md) |
| `ticket-reply` | Embedded reply form on a ticket | `templates/note/ticket/show.html.twig` (`formFrame="ticket-reply"`) |

A response must contain a `<turbo-frame>` with the id the request targeted. A link inside `reports` or `{model}-table` that leaves the frame needs `data-turbo-frame="body"`.

### Links

| To | Use |
|----|-----|
| Show another page in the main content | `data-turbo-frame="body"` (or no attribute inside `body`) |
| Open a form, confirmation or action in a modal | `data-turbo-frame="modal"` |
| A link that opens a confirm page or the modal | also `data-turbo-prefetch="false"` |
| Log in or out | `data-turbo-frame="_top"` |

State-changing actions are a POST form, never a link (see [FormFlow](../FormFlow/README.md)).

### Modal-capable templates

- Extend `shared/turbo/modal_base.html.twig`. The same template then works as a modal, as an embedded frame and as a full page.
- Wrap the content in `<twig:ModalPanel title="…" size="sm|md|lg|xl">` (default `md`; `ConfirmDialog` uses `sm`). On desktop `md` is half the viewport wide; `sm`, `lg` and `xl` have fixed maximum widths (`templates/components/Modal.html.twig`). Its heading has `id="modal-title"`, which names the dialog for screen readers.
- One modal at a time. Do not open a second modal from inside one; a link inside the modal replaces its content in place (see [Gotchas](#gotchas)).

### Layout by `Turbo-Frame` header

`modal_base.html.twig` picks its parent from the request's `Turbo-Frame` header:

| Header | Extends | Renders |
|--------|---------|---------|
| `modal` | `shared/turbo/modal_frame.html.twig` | Only `<turbo-frame id="modal">` and the flash stream |
| any other value | `shared/turbo/embedded_frame.html.twig` | A frame with that id and the flash stream — see [Embedded forms](../FormFlow/EmbeddedForms.md) |
| none | `base.html.twig` | The full page |

### Which frame a form submits to

`<twig:FlowForm>` sets `data-turbo-frame` on its submit button in this order: the `formFrame` prop, then the request's `Turbo-Frame` header, then `_top`. So a form opened in the modal submits to the modal, and a form loaded as a full page submits to the whole page.

## How to

### Open a form in a modal

```twig
{# the link (templates/note/ticket/_ticket_card.html.twig) #}
<a href="{{ path('app_note_ticket_reassign', {'id': ticket.publicId.value}) }}"
   class="…"
   data-turbo-frame="modal"
   data-turbo-prefetch="false">
    <twig:ux:icon name="bi:arrow-left-right" class="h-3 w-3"/>
    Reassign
</a>

{# the template the controller renders (templates/note/ticket/reassign.html.twig) #}
{% extends 'shared/turbo/modal_base.html.twig' %}
{% block title %}Reassign Ticket{% endblock %}
{% block body %}
    <twig:ModalPanel title="Reassign Ticket">
        <twig:FlowForm :form="form" buttonLabel="Reassign"/>
    </twig:ModalPanel>
{% endblock %}
```

The default FormFlow templates in `templates/shared/form_flow/` (create, update, delete, filter) already extend `modal_base.html.twig`; see [FormFlow](../FormFlow/README.md).

### Embed a form in a page

See [Embedded forms](../FormFlow/EmbeddedForms.md). The ticket reply form is the live example.

## What a submit returns

`TurboAwareRedirector` (`vendor/myvars/form-flow/src/Redirect/TurboAwareRedirector.php`) decides, for every successful flow redirect:

| Case | With a `Turbo-Frame` header | Without |
|------|-----------------------------|---------|
| Default (`forCreate`, `forUpdate`, `forAction`, …) | `<turbo-stream action="refresh">` | 303 redirect |
| `FlowContext::forDelete()` / `forConfirm()` | `redirect` stream to the success URL when its path differs from the referer's; otherwise (or no referer) `refresh` | 303 redirect |
| The handler returned a `RedirectTarget` (every `*FilterHandler`, some create and ticket handlers) | Always a `redirect` stream to that URL | redirect with the target's status |

So a filter submitted in the modal lands on the index URL with the filter params. There is no per-call option to change this. `ActionFlow` redirects the same way after a failed result too (with an error flash). `FormFlow` re-renders the form with 422 on invalid input or `Result::fail()`.

## The modal

`templates/shared/turbo/_modal.html.twig` is included once in `base.html.twig`. It holds the `modal` frame and a spinner inside `<twig:Modal>`; the `<dialog>` and the `basic_modal` controller are in `templates/components/Modal.html.twig`.

- **Opens** on `turbo:frame-load` of the modal frame, sized from the first `[data-modal-size]` element in the loaded content (`ModalPanel` sets it). Without one the size is `md`.
- **Loading.** The frame's `turbo:before-fetch-request` / `turbo:frame-render` call `frameBusy` / `frameIdle`, which set and clear `data-loading`. Tailwind classes on `_modal.html.twig` (`data-[loading]:opacity-30` on the frame, `peer-data-[loading]:flex` on the spinner) do the rest; there is no separate CSS.
- **Closes** on a successful submit (`turbo:submit-end` with `success`; a 422 keeps it open with the errors), on Escape (the native `close` event), on the `ModalPanel` × button and any Cancel button wired to `basic-modal#close`, on a backdrop click, and on `turbo:before-cache`, so a cached page never holds an open modal.
- **Closing clears it.** `close()` resets the size to `md` and removes the frame's `src` and HTML.
- **Backdrop close** needs both mousedown and mouseup on the backdrop, so dragging a text selection out of the dialog does not close it.

## Gotchas

- **Where flashes render.** `base.html.twig` renders flashes into the Sonner toaster (`#flash-container`) only when there is no `Turbo-Frame` header. A successful submit returns a `refresh` or `redirect` stream, which loads a page without the header, so that page shows the flash. When a frame template is rendered (for example the 422 re-render after `Result::fail()`), `_frame_success_stream.html.twig` appends the flashes to `#flash-container`, but only if the request's header matches the frame it was included for. "Flash not showing" or "flash shown twice" means one of these paths is missing or both ran.
- **`redirect` is a custom stream action**, registered in `assets/app.js`; it calls `visit(url, {action: 'advance'})`.
- **History.** The search box (`assets/controllers/searchbox_controller.js`) sets the results frame's `src` and calls `history.replaceState`, so typing adds no history entries. Pagination links inside `{model}-table` use the frame's `advance`, so each page is a history entry.
- **Search streams.** The results frame also replaces `{model}-result-count` and `{model}-search-filter`, so the count and the filter button's active state follow the results.
- **Turbo meta tags are off in tests.** `base.html.twig` omits `turbo-view-transition`, `turbo-refresh-method` (`replace`) and `turbo-refresh-scroll` (`preserve`) when `app.environment` is `test`.
- **View transitions.** `assets/app.js` wraps frame renders in `document.startViewTransition` and turns view transitions off for restore visits on iOS Safari, whose own swipe animation would otherwise run twice.
- **A link inside the modal replaces the modal's content in place.** It has no `data-turbo-frame`, so it loads into the `modal` frame: the update form's Delete link (`templates/shared/form_flow/update.html.twig`) swaps the form for the delete confirmation.
- **The modal's size is read only when it opens** (`frameLoaded` in `assets/controllers/basic_modal_controller.js`). Content loaded into an open modal keeps the first size, so an `sm` confirmation reached from an `md` form stays `md`.
- **`refresh="morph"` on `body`, `{model}-table` and `reports` does nothing today.** A frame's `refresh="morph"` acts only in a morphing page refresh or on `frame.reload()`. `turbo-refresh-method` is `replace` (`base.html.twig`) and nothing calls `reload()`. Scroll survives a refresh because of `turbo-refresh-scroll: preserve`.

## Related

- [ADR 007: Turbo frame modal architecture](../../adr/007-turbo-frame-modal-architecture.md)
- [ADR 008: Server-driven UI](../../adr/008-server-driven-ui-architecture.md)
- [FormFlow](../FormFlow/README.md)
- [Embedded forms](../FormFlow/EmbeddedForms.md)
- [Stimulus controllers](../UI/StimulusControllers.md) — `basic_modal`, `searchbox`
- [InlineEdit](../InlineEdit/README.md)
