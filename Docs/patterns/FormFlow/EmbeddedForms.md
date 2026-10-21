# Embedded Forms

A FormFlow form rendered inside another page, in its own Turbo Frame, instead of in the modal. Read when a form belongs on a host page, such as the reply form under a ticket.

## Rules

| Scenario | Use |
|---|---|
| Quick action (edit, reassign, confirm) | Modal |
| Form that belongs on another page (reply, comment) | Embedded |
| Form visited as its own page | Full page |

- **Render the form server-side on the host page**, not lazy-loaded with a frame `src`, so it is part of the page Turbo re-renders after the submit.
- **The handler returns no `RedirectTarget`.** A plain `Result::ok()` makes the flow answer with `<turbo-stream action="refresh">`, which re-renders the host page in place with scroll kept (`base.html.twig` sets `turbo-refresh-method` to `replace` and `turbo-refresh-scroll` to `preserve`). A `RedirectTarget` always navigates.

How a frame request picks its layout (`modal_base.html.twig` and the `Turbo-Frame` header) and how `FlowForm` picks the submit frame (`formFrame`) are in [Turbo](../Turbo/README.md).

## How to

The reply form on the ticket page is the live example.

1. **Template for the POST response** (shown with validation errors), `templates/note/ticket/reply_embedded.html.twig`. It extends `shared/turbo/modal_base.html.twig` and does not use `<twig:ModalPanel>`:

   ```twig
   {% extends 'shared/turbo/modal_base.html.twig' %}

   {% block body %}
       <twig:FlowForm :form="form" buttonLabel="Send Reply"/>
   {% endblock %}
   ```

2. **Host controller** (`TicketController::show()`) creates the form with an explicit `action` URL pointing at the flow route (`'action' => $this->generateUrl('app_note_ticket_reply', [...])`) and passes the view to the host template (`templates/note/ticket/show.html.twig`), which wraps it in a frame and names that frame in `formFrame`:

   ```twig
   <turbo-frame id="ticket-reply">
       <twig:FlowForm :form="replyForm" buttonLabel="Send Reply" formFrame="ticket-reply"/>
   </turbo-frame>
   ```

3. **Flow controller** (`TicketController::reply()`) points at the template and the host page:

   ```php
   context: FlowContext::forCreate($this->model())
       ->template('note/ticket/reply_embedded.html.twig')
       ->successRoute('app_note_ticket_show', ['id' => $ticket->getPublicId()->value()]),
   ```

4. **Handler** (`ReplyToTicketHandler`) returns `Result::ok(message: 'Reply sent')`, no `RedirectTarget`.

## Modal versus embedded

| Aspect | Modal | Embedded |
|---|---|---|
| `<twig:ModalPanel>` | Yes | No |
| Host page markup | `<a data-turbo-frame="modal">` | `<turbo-frame>` with the form inside |
| Frame id | `modal` | Your own (`ticket-reply`) |
| On success | Modal closes, page refreshes | Page refreshes in place, scroll kept |
| Handler `RedirectTarget` | Either works | Avoid |
| Form created by | FormFlow | Host controller (GET) and FormFlow (POST) |

## Related

- [FormFlow](README.md)
- [Turbo](../Turbo/README.md)
