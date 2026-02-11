# Embedded Forms

Embedded forms render a FormFlow form inline within another page, as opposed to in a modal dialog. They use Turbo Frames to scope form submissions without full-page navigation.

## When to Use

| Scenario | Use |
|----------|-----|
| Quick action (edit, reassign, confirm) | Modal |
| Form that belongs in context of another page (reply, comment) | Embedded |
| Form visited directly as its own page | Full-page |

## How It Works

### Three-Way Layout Detection

`modal_base.html.twig` inspects the `Turbo-Frame` request header:

1. **`modal`** — extends `modal_frame.html.twig` (modal dialog)
2. **Any other value** — extends `embedded_frame.html.twig` (inline frame)
3. **Absent** — extends `base.html.twig` (full page)

### Submit Frame Target

`FlowForm` sets `data-turbo-frame` on the submit button. Priority:

1. `formFrame` prop (explicit override)
2. `Turbo-Frame` request header value
3. `_top` fallback

## Step-by-Step

### 1. Create an Embedded Template

Extends `modal_base.html.twig` but does **not** use `<twig:Dialog>`. This handles POST responses (validation errors):

```twig
{% extends 'shared/turbo/modal_base.html.twig' %}

{% block body %}
    <p class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Form Title</p>
    <twig:FlowForm :form="form" buttonLabel="Submit"/>
{% endblock %}
```

### 2. Render the Form on the Host Page

In the host controller, create the form with an explicit `action` URL:

```php
$form = $this->createForm(YourType::class, new YourForm(), [
    'action' => $this->generateUrl('app_context_entity_action', ['id' => $entity->getPublicId()->value()]),
])->createView();
```

In the host template, wrap it in a `<turbo-frame>` and pass `formFrame`:

```twig
<turbo-frame id="your-form">
    <twig:FlowForm :form="yourForm" buttonLabel="Submit" formFrame="your-form"/>
</turbo-frame>
```

The form must be rendered server-side (not lazy-loaded via `src`) so that Turbo morph can patch it in place after submission.

### 3. Configure the Handler Controller

Point to the embedded template and set `successRoute` to the host page:

```php
context: FlowContext::forCreate($this->model())
    ->template('your_context/your_embedded_form.html.twig')
    ->successRoute('app_context_entity_show', ['id' => $entity->getPublicId()->value()]),
```

### 4. Avoid RedirectTarget in the Handler

Return `Result::ok()` without a `RedirectTarget`. This lets `TurboAwareRedirector` use `<turbo-stream action="refresh">`, which morphs the page smoothly.

```php
return Result::ok(message: 'Saved');
```

## Worked Example: Ticket Reply

**Key files:**
- `templates/note/ticket/reply_embedded.html.twig` — embedded template for POST responses
- `templates/note/ticket/show.html.twig` — host page renders form inline in `<turbo-frame>`
- `src/Note/UI/Http/Controller/TicketController.php` — `show()` creates the form, `reply()` handles submission
- `src/Note/Application/Handler/Ticket/ReplyToTicketHandler.php` — returns `Result::ok()` without redirect

## Comparison: Modal vs Embedded

| Aspect | Modal | Embedded |
|--------|-------|----------|
| Uses `<twig:Dialog>` | Yes | No |
| Host page markup | `<a data-turbo-frame="modal">` | `<turbo-frame>` with inline form |
| Frame ID | `modal` | Custom (e.g., `ticket-reply`) |
| On success | Modal closes + page refreshes | Page morphs in place |
| Handler redirect | Either works | Avoid `RedirectTarget` |
| Form creation | FormFlow only | Host controller + FormFlow |
