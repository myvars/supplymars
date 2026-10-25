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
| A GET link with a side effect (only logout today) | also `data-turbo-prefetch="false"` |
| Log in or out | `data-turbo-frame="_top"` |

State-changing actions are a POST form, never a link (see [FormFlow](../FormFlow/README.md)). So hover prefetch of a link that opens a modal or confirm page only GETs a page with no side effect; such a link needs no `data-turbo-prefetch="false"`, though some still have it. A live page turns prefetch off for the whole page ([Live updates](#live-updates-with-mercure)).

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
- **Stays open through a live refresh.** `open()` marks the `<dialog>` `data-turbo-permanent` and `close()` removes it; `keepScrollLock` cancels the morph of `<body>`'s class while it is open ([What a refresh keeps](#what-a-refresh-keeps)).

## Live updates with Mercure

A flash reaches only the person who made the request. To tell another user, on whatever page they have open, call `UserNotifierInterface` (`src/Shared/Application/`). The example: releasing another user's order lock shows the holder a toast, "Sam Carter released your lock on order #000123", with a View link ([ADR 017](../../adr/017-live-updates-with-mercure.md)).

```php
$this->notifier->notify(
    userId: $message->holderId,          // the user's public ID
    type: 'info',                        // a flash type: success, info, warning, danger
    message: sprintf('%s released your lock on order #%06d', $name, $order->getId()),
    url: $this->urlGenerator->generate('app_order_show', ['id' => $message->orderId]),
);
```

`src/Order/Application/Listener/NotifyLockHolderOfRelease.php` is the reference: a sync listener that queues a message on `async` only when someone other than the holder released the lock, and a handler for that message that notifies.

### How it works

| Piece | Where |
|-------|-------|
| `MercureUserNotifier` renders a Turbo Stream that appends a `<twig:FlashToast>` (with its optional `url`, a View link) to `#flash-container`, and publishes it through `LiveUpdatePublisherInterface` as a **private** update on the topic `user/{publicId}` | `src/Shared/Infrastructure/Notification/`, `templates/shared/turbo/_user_notification_stream.html.twig` |
| Every logged-in page subscribes to its own user's topic with a `<turbo-mercure-stream-source private>`. Rendering it sets the cookie (`__Secure-mercure_access_token`, path `/.well-known/mercure`) whose token allows that topic, plus the page's [live topics](#live-page-updates), and nothing else | `templates/base.html.twig`, outside `<turbo-frame id="body">`, in a `data-turbo-permanent` element, so Turbo visits keep it. Its id names the user: a visit that finds another user logged in (from another tab) replaces it |
| Logging out clears the cookie, so a shared browser stops receiving the previous user's toasts | `ClearMercureCookieOnLogout` |
| The hub: FrankenPHP's own under `make up` and in production; the `mercure` container of `make up-dev-tools` under `symfony serve`, served on the site's origin by its proxy | `docker/frankenphp/Caddyfile`, `compose.override.yaml`, `.symfony.local.yaml` |

The hub is on the site's own origin in every environment, so `connect-src 'self'` covers it and the cookie needs no domain.

### Rules

- **Fire-and-forget.** The hub keeps no history (`transport local`). A user with no page open never sees the toast, and one whose connection drops misses what was sent meanwhile. The cookie's token lasts an hour from the last page load, so a tab left on one page longer stops receiving once its connection drops, until the next visit. Anything that must not be lost belongs in an email or a stored record.
- **Never throws.** `notify()` and `publish()` catch and log a hub failure (`Live update not published`). A toast is worth nothing a minute late, so a hub outage should not fail the message: it would be retried for no use and end in the `failed` transport. A broken template still throws: that is a bug, not an outage.
- **Notify from the worker, not the request.** Queue a message on `async` (`#[AsMessage('async')]`) and notify from its handler, so a slow or down hub never slows a page or a scheduled job. Do not make a domain event async for this: other listeners rely on it being sync.
- **Render only what you pass.** The update is rendered in the worker, with no request and no current user. Put nothing in the stream beyond the message for that one user.
- **Not the actor.** Skip the notification when the person it would tell did the thing.
- **Private topics only.** Do not publish public updates or use `#[Broadcast]` on entities without a decision of your own: it puts UI in `Domain/` and sends every Doctrine change rather than domain events.

### Testing

`config/services.yaml` (`when@test`) replaces the hub with `tests/Shared/Mercure/RecordingHub`, which records each update instead of sending it, or throws after `failWith()`. `QueuedNotifications` (beside it) handles everything a request queued, as the worker would, and reads the recorded updates. Intercept the redirect (`->interceptRedirects()`): following it starts a new kernel with an empty queue and hub. See `tests/Order/UI/LockReleaseNotificationFlowTest.php`, and `tests/Shared/UI/Http/UserNotificationSubscriptionFlowTest.php` for the subscription and its cookie (`tests/Shared/Mercure/MercureCookie` reads what it grants).

The test queue serializes its messages (`in-memory://?serialize=true`, `config/packages/messenger.yaml`), as the doctrine queue does, so a message or stamp that cannot be stored fails in tests too.

## Live page updates

A page can change for everyone looking at it when something on it changes. The example: everyone with an order open sees its items, purchase orders, statuses, totals and lock change, whether a staff user or the simulation changed them ([ADR 017](../../adr/017-live-updates-with-mercure.md)).

| Live page | Topic |
|-----------|-------|
| The order page (`OrderController::show`) | `order/{id}` |
| A purchase order's page (`PurchaseOrderController::show`) | its order's, so it refreshes with the order page: a change to another purchase order on the same order refreshes it too |

### Declare the page's topic

Name the topic `{resource}/{id}` in one static method, pass it to the page's template as `live_topics`, and mark the action `#[Cache(noStore: true)]`. Put the method in the context's `Domain/`, so a page in another context can subscribe to it under Deptrac:

```php
// src/Order/Domain/Model/Order/CustomerOrder.php
public static function liveTopic(OrderPublicId $orderId): string
{
    return 'order/' . $orderId->value();
}

// OrderController::show, under #[Route] and #[Cache(noStore: true)]
return $this->render('/order/show.html.twig', [
    'result' => $order,
    'live_topics' => [CustomerOrder::liveTopic($order->getPublicId())],
]);
```

`base.html.twig` does the rest. The request's one subscriber cookie (set by the permanent per-user element) grants the user's topic and the page's. A second element, inside `<turbo-frame id="body">`, subscribes to the page's topics and goes when the page does: the order page is opened in that frame, so outside it a frame visit would keep the previous page's subscription. Do not call `turbo_stream_from(…, private: true)` in a page: the cookie is already set, and a second call throws.

**Back must request the page again.** The cookie is set only by loading the page, and the last page loaded has replaced it. A copy restored without a request would reconnect without this page's topics and receive nothing, while looking live. Two things prevent it:

- Turbo's own cache: `base.html.twig` adds `<meta name="turbo-cache-control" content="no-cache">` to a page with `live_topics`.
- The browser's cache, which serves Back across full page loads even for a page that must be revalidated: `#[Cache(noStore: true)]` on the action. This one is yours to add.

### Queue one refresh per page

The events behind a page are usually sync, and one flush can raise hundreds of them (a simulation command flushes once per run). Listen to them, find the page they belong to, and queue one refresh per page, deduplicated by Messenger:

```php
$this->bus->dispatch(new RefreshOrderPage($orderId), [
    new DeduplicateStamp('order-page-refresh.' . $orderId, onlyDeduplicateInQueue: true),
]);

#[AsMessageHandler]
public function refresh(RefreshOrderPage $message): void
{
    $this->publisher->refresh(CustomerOrder::liveTopic(OrderPublicId::fromString($message->orderId)));
}
```

While a refresh for the order is queued, another is dropped where it is sent. The lock is released when the worker receives the message, so a change after that queues another. These locks are in Redis (the `deduplication` store in `config/packages/lock.yaml`, given to Messenger's two deduplication services in `config/services.yaml`), because the message is sent in one container and handled in another, and a flock key cannot be serialized onto the queue. Every other lock stays on `LOCK_DSN` (`flock`).

`LiveUpdatePublisherInterface` (`src/Shared/Application/`) logs a hub failure and never throws, like `notify()`.

### Refresh or rendered HTML

| Publish | When | Cost |
|---------|------|------|
| `refresh($topic)`: each browser requests the page again, under its own login | The page has per-viewer content: the lock button's label, actions a role decides. Most pages | One page request per viewer per update, or two after the viewer arrived by a link in the body frame (Turbo then also reloads that frame) |
| `publish($topic, $turboStream)` with HTML rendered in the worker | The content is the same for every viewer (a status badge, a counter), or many people watch the page | None beyond the hub |

The update is rendered once in the worker, with no viewer. Never put per-viewer content in rendered HTML: everyone would see the first viewer's links, or none.

### What a refresh keeps

`refresh()` publishes `<turbo-stream action="refresh" method="morph" scroll="preserve">`, Turbo 8's broadcast refresh ([handbook](https://turbo.hotwired.dev/handbook/page_refreshes)). Turbo morphs the page in place, keeps the scroll position, debounces a burst into one refresh, and skips a refresh that arrives during a visit. The page's own refreshes (after a submit) still replace it (`turbo-refresh-method`).

A morph replaces anything the server's new HTML does not have, so each thing the viewer can be in the middle of protects itself, where it is defined, with Turbo's own tools:

| Kept through a live refresh | How | Where |
|-----------------------------|-----|-------|
| An open modal (adding or editing an order item) | `data-turbo-permanent` on the `<dialog>` while open; cancels the morph of `<body>`'s class, which holds the scroll lock | `basic_modal` controller |
| An open kit component: the sidebar menu and the help panel (`Sheet`, with the help loaded into it), the header's user menu (`DropdownMenu`) | `data-turbo-permanent` on the component's root while open; added where the component is used, so the kit's files stay as installed | `live_open` controller (`_menu.html.twig`, `_help_drawer.html.twig`, `_header.html.twig`) |
| Toasts on screen, often a live toast that came with the same change | Cancels the morph of the toaster, then adds any new flash from the server's HTML | `live_toaster` controller round the toaster (`base.html.twig`) |

The rules for anything new:

- **Set `data-turbo-permanent` from JavaScript, while the viewer is busy, never in the server's HTML.** A morph skips any element that has it. Turbo's other renders (a visit, a frame, a stream) keep an element only when the new HTML marks the same id permanent too, so an attribute the server never sends cannot carry a stale element into the next page.
- **Or cancel `turbo:before-morph-element` / `turbo:before-morph-attribute`** for one element or attribute, as `basic_modal` does for the scroll lock.
- **A form or an inline edit on a live page** is replaced by the server's version when a refresh arrives. The order page has none of its own (its forms open in the modal), so the skeleton's `live_form` controller and its `inline_edit` change are not here. Bring them over before putting either on a live page.
- **A kit component that opens** (`Sheet`, `DropdownMenu`, `Dialog`, `AlertDialog`) placed on a live page gets `data-controller="live-open"` where it is used. `live_open` needs an id inside the component, so the morph can tell which element is the kept one, and none on the component's root. `Popover` and `DatePicker` have no id inside.

### Rules

- **The page's access check is its subscription check.** A browser gets the cookie for `order/X` only by loading a page the user may see, so `#[IsGranted]` decides who receives updates. Put `live_topics` only on a page that checks access to that resource.
- **Private only.** Every update is private.
- **Fire-and-forget.** No history: a tab whose connection drops misses what was sent meanwhile, and the next visit shows the current state. The cookie is per browser and lasts an hour from the last page load, so if another tab loads a different page, or the hour passes, and this tab's connection then drops, this tab stops receiving its page's updates until its next visit.
- **No hover prefetch on a live page.** A prefetched response sets the cookie too, without this page's topics, so `base.html.twig` adds `turbo-prefetch: false` to a page with `live_topics`.
- **The actor's tab refreshes too.** Locking or allocating POSTs into the body frame and re-renders the page; the live refresh that follows finds nothing new.
- **Latency.** The worker sleeps up to 5 seconds between polls, and in production handles `async` after the scheduled job that caused the refresh. Under `symfony serve`, the worker is the one the server starts (`.symfony.local.yaml`); `symfony server:status` lists it and `symfony server:log` shows its output.

### Testing

- **Subscription:** visit the page and check its stream sources, its `no-cache` meta and `no-store` header, and that the cookie grants exactly `user/{ownPublicId}` and the page's topics (`MercureCookie::grantedTopics()`). See `tests/Order/UI/OrderLiveUpdatesFlowTest.php`. `StateBetweenRequestsTest` checks that each order page's cookie grants only its own viewer's topic and its own order's.
- **Publishing:** make the change as a caller would, then `QueuedNotifications::handle()` and read `QueuedNotifications::published()`. Compare a refresh with `MercureLiveUpdatePublisher::REFRESH_STREAM`. Check the count of handled messages to prove one refresh per order (`tests/Order/UI/Console/SimulationRefreshesOrderPagesTest.php` runs the simulation's commands).
- **In a browser:** what a refresh keeps runs in the browser, which PHPUnit does not see. With two staff users, check that a change appears for the other, that a live toast stays on screen, that an open item modal, the sidebar menu, the help panel (with its help) and the user menu survive, and that the page still updates after Back.

## Gotchas

- **Where flashes render.** `base.html.twig` renders flashes into the Sonner toaster (`#flash-container`) only when there is no `Turbo-Frame` header. A successful submit returns a `refresh` or `redirect` stream, which loads a page without the header, so that page shows the flash. When a frame template is rendered (for example the 422 re-render after `Result::fail()`), `_frame_success_stream.html.twig` appends the flashes to `#flash-container`, but only if the request's header matches the frame it was included for. "Flash not showing" or "flash shown twice" means one of these paths is missing or both ran.
- **`redirect` is a custom stream action**, registered in `assets/app.js`; it calls `visit(url, {action: 'advance'})`.
- **History.** The search box (`assets/controllers/searchbox_controller.js`) sets the results frame's `src` and calls `history.replaceState`, so typing adds no history entries. Pagination links inside `{model}-table` use the frame's `advance`, so each page is a history entry.
- **Search streams.** The results frame also replaces `{model}-result-count` and `{model}-search-filter`, so the count and the filter button's active state follow the results.
- **Turbo meta tags are off in tests.** `base.html.twig` omits `turbo-view-transition`, `turbo-refresh-method` (`replace`) and `turbo-refresh-scroll` (`preserve`) when `app.environment` is `test`.
- **View transitions.** `assets/app.js` wraps frame renders in `document.startViewTransition` and turns view transitions off for restore visits on iOS Safari, whose own swipe animation would otherwise run twice.
- **A link inside the modal replaces the modal's content in place.** It has no `data-turbo-frame`, so it loads into the `modal` frame: the update form's Delete link (`templates/shared/form_flow/update.html.twig`) swaps the form for the delete confirmation.
- **Errors in the modal.** An error page answering a `modal` frame request (a confirm link for a record someone else deleted) renders inside `<turbo-frame id="modal">` as a small `ModalPanel` (`templates/bundles/TwigBundle/Exception/_layout.html.twig`). Any other request gets the full error page, whose `body` frame also answers body-frame navigation. A request from another embedded frame still gets the full page, which has no matching frame. Error pages render only without debug; in dev, Symfony's exception page shows instead.
- **The modal's size is read only when it opens** (`frameLoaded` in `assets/controllers/basic_modal_controller.js`). Content loaded into an open modal keeps the first size, so an `sm` confirmation reached from an `md` form stays `md`.
- **`refresh="morph"` on `body`, `{model}-table` and `reports` acts only in a live refresh.** A frame's `refresh="morph"` acts only in a morphing page refresh or on `frame.reload()`. `turbo-refresh-method` is `replace` (`base.html.twig`), so only a live refresh (`method="morph"`, [above](#what-a-refresh-keeps)) morphs; it then also reloads the body frame when a visit gave it a `src`. Scroll survives a refresh because of `turbo-refresh-scroll: preserve`.

## Related

- [ADR 007: Turbo frame modal architecture](../../adr/007-turbo-frame-modal-architecture.md)
- [ADR 008: Server-driven UI](../../adr/008-server-driven-ui-architecture.md)
- [ADR 017: Live updates with Mercure](../../adr/017-live-updates-with-mercure.md)
- [FormFlow](../FormFlow/README.md)
- [Embedded forms](../FormFlow/EmbeddedForms.md)
- [Stimulus controllers](../UI/StimulusControllers.md) — `basic_modal`, `searchbox`, `live_open`, `live_toaster`
- [InlineEdit](../InlineEdit/README.md)
