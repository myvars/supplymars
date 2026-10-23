# ADR 017: Live Updates with Mercure: Private User Toasts and Resource Topics

## Status

Accepted (#116). Brings over the skeleton's pattern: its ADR 016 (private per-user toasts, myvars/skeleton#115) and ADR 017 (live pages on resource topics, myvars/skeleton#117), and the kit protections of myvars/skeleton#121. Those ADRs hold the history and the alternatives tried there; this one records what supplymars uses and where it differs.

## Context

FrankenPHP includes a Mercure hub ([ADR 016](016-frankenphp-in-place-of-nginx-and-php-fpm.md)), unused until now.

- **The order page goes stale.** While a staff user has `/order/{id}` open, the simulation changes it: purchase orders are accepted four times an hour, items shipped and delivered hourly (`MainSchedule.php`). The page shows none of it until reloaded.
- **A released lock goes unnoticed.** Any staff user can release any lock (`LockOrderHandler`), and the simulation skips a locked order: allocation (`CustomerOrderDoctrineRepository::findNextOrdersToBeProcessed`), acceptance (`PurchaseOrderDoctrineRepository::findWaitingPurchaseOrders`), shipping and delivery (`PurchaseOrderItemDoctrineRepository::findPurchaseOrderItemsByStatus`). The holder needs to know that automation can pick the order up again.
- **One flush raises many events.** Each simulation command flushes once per run (`AcceptPOsCommand`, `ShipPOItemsCommand`), so one flush can raise hundreds of status events across many orders. All of them are sync, and Audit, Pricing and the badge cache listen to them.
- **Two containers in production.** `php` serves pages; `messenger` runs the scheduled jobs and the `async` queue.

## Decision

### Shared: the skeleton's pattern

- **A publisher and a notifier** in `src/Shared/`: `LiveUpdatePublisherInterface` (`publish($topic, $turboStream)`, `refresh($topic)`) and `UserNotifierInterface` (`notify($userId, $type, $message, $url)`), implemented by `MercureLiveUpdatePublisher` and `MercureUserNotifier` (`Infrastructure/Notification/`). Every update is **private**. A hub failure is logged (`Live update not published`), never thrown. A toast is a `<twig:FlashToast>` (which gains an optional View link) appended to `#flash-container`, on the topic `user/{publicId}`. The notifier takes a plain string ID, because Shared imports from no business context.
- **One subscription point** in `base.html.twig`:
  - a permanent per-user element, which sets the request's one subscriber cookie, granting `user/{publicId}` and the page's `live_topics`;
  - a non-permanent element for the page's topics, inside `<turbo-frame id="body">`, because the order page is opened in that frame and a frame visit must drop the previous page's subscription;
  - `turbo-cache-control: no-cache` on a live page, and `#[Cache(noStore: true)]` on its action, so Back requests it again rather than reconnecting with another page's cookie;
  - `turbo-prefetch: false` on a live page. Every page response replaces the cookie, so a link prefetched on hover would leave a cookie without this page's topics, and the page would go quiet at its next reconnect. The skeleton does not have this.
- **The page's access check is its subscription check.** The cookie for `order/X` is set only by rendering the order page, which is `ROLE_ADMIN`.
- **A refresh, not rendered HTML.** The order page differs by viewer (the lock button's label, the actions), and an update is built in the worker with no viewer. The refresh carries `method="morph"`; the page's own `turbo-refresh-method` stays `replace`. What the viewer is in the middle of protects itself with `data-turbo-permanent`, set from JavaScript only while it is open: `basic_modal` (an open modal, and the `<body>` scroll lock), `live_open` (the sidebar and help `Sheet`s and the user menu `DropdownMenu`), `live_toaster` (the toaster). The skeleton's `live_form` and its `inline_edit` change are not brought over: the order page has no form or inline edit of its own, only one-button POSTs and modals.
- **Logout clears the cookie** (`ClearMercureCookieOnLogout`).
- **The hub is on the site's origin everywhere**, so `connect-src 'self'` and the `__Secure-` cookie need nothing extra: FrankenPHP's own hub under `make up` and in production (a `mercure` block in the Caddyfile's `route`, `transport local`, one hub `name`, `resource_identifier {$MERCURE_PUBLIC_URL}`); a `dunglas/mercure:v1.1.4` container in `make up-dev-tools`, proxied by `symfony serve` (`.symfony.local.yaml`, `proxy_mercure`). One secret, `MERCURE_JWT_SECRET`, signs both tokens; the issuer is the fixed `MERCURE_JWT_ISSUER`.

### Order: where supplymars differs

- **The refresh goes through the queue, one per order.** The skeleton made its events async and refreshed from their handlers. Here that would change when the Audit, Pricing and badge listeners run. Instead a sync listener, `RefreshOrderPageForViewers`, resolves the order each event belongs to (the events carry their own IDs, so it asks the repository interfaces, Purchasing's included) and dispatches a `RefreshOrderPage` message to `async` with Messenger's `DeduplicateStamp`, keyed on the order, `onlyDeduplicateInQueue`. However many events a flush raises, the queue holds one refresh per order, and no request or scheduled job calls the hub. The lock is released when the worker receives the message, so a change made after that queues another.
- **Deduplication locks in Redis; every other lock stays on flock.** Deduplication takes a lock where the message is sent (`php`, or the scheduled job in `messenger`) and releases it where the message is handled (`messenger`). `LOCK_DSN=flock` could do neither: a flock key cannot be serialized onto the doctrine queue (`UnserializableKeyException`), and a file lock exists in one container only. `config/packages/lock.yaml` adds a `deduplication` store on the `snc_redis.default` client, the one the cache uses, which both containers reach, and `config/services.yaml` gives it to Messenger's `deduplicate_middleware` and `release_deduplication_lock_on_failure_listener`, which otherwise take the default factory. Tests use the `in-memory` store for it, and the test queue serializes its messages (`in-memory://?serialize=true`) as the real one does.
- **The events that were never raised now are.** `OrderItemWasCreatedEvent`, `OrderItemWasChangedEvent`, `PurchaseOrderWasCreatedEvent` and `PurchaseOrderItemWasCreatedEvent` existed but nothing raised them; `CustomerOrderItem::createFromProduct()` and `updateItem()`, `PurchaseOrder::createFromOrder()` and `PurchaseOrderItem::createFromCustomerOrderItem()` now do. `OrderItemWasChangedEvent` gets its own type, `ORDER_ITEM_CHANGED`, in place of `ORDER_ITEM_CREATED`.
- **An item removal event.** Removing an item (quantity 0 on the edit form) deletes it, so an event it raised could not be traced to its order. `CustomerOrder::removeCustomerOrderItem()` raises `OrderItemWasRemovedEvent` with the order's ID instead.
- **A lock event.** `CustomerOrder::lockOrder($holder, $changedBy)` raises `OrderLockWasChangedEvent` (the order, the previous holder, the new holder or null, and who acted) when the holder changes. `LockOrderHandler` passes in the signed-in user as `changedBy`, because the holders alone cannot tell who released a lock. `NotifyLockHolderOfRelease` sends a `NotifyOrderLockReleased` message, through `async` without deduplication, only when a user loses a lock they held to someone else's action (released, or, outside the toggle, given to another holder); its handler tells the holder "Sam Carter released your lock on order #000123", with a View link. Taking a lock (the toggle only takes a free one), releasing your own, or a change with no previous holder sends nothing.

## Consequences

- Up to about 5 seconds between a change and the refresh: the worker's `--sleep=5`. In production a refresh caused by a scheduled job runs after that job, because one worker consumes `scheduler_default` and then `async` (`compose.prod.yaml`).
- Under `symfony serve`, live refreshes and toasts need `symfony console messenger:consume async`, as queued email does.
- Each event costs the request or job one repository lookup and one Redis call; a duplicate is dropped there, before it reaches the queue. If Redis is down, sending fails, as sessions and the cache already do.
- A refresh dropped as a duplicate is not lost: the one queued before it has not been received yet, so the page it triggers shows both changes.
- If the worker is down, each order's lock frees itself after its TTL (300 s), so at most one refresh per order per five minutes queues up.
- The cookie is per browser and its token lasts an hour from the last page load. When a live page's connection drops and reconnects (the hub closes idle connections), it is granted only what the last-set cookie allows: after another tab loads a page, or after the hour, the page stops updating until the next visit. Acceptable for fire-and-forget; the hover prefetch above was the common case.
- Fire-and-forget: no history. A missed refresh is fine, because the next visit shows the current state. A missed toast is lost.
- **The playground has one shared login** (`demo@supplymars.com`), so the holder and the releaser are always the same user and the lock toast never fires there. Live refreshes work as on live.
- **The simulation acts as `admin@supplymars.com`.** No console command changes a lock today. If one does, a system release would toast "admin released your lock"; `NotifyLockHolderOfRelease` says to decide then.
- The viewer's own action (lock, allocate) POSTs into the body frame and re-renders the page; the live refresh that follows finds nothing new.
- Each open logged-in tab holds one SSE connection to Caddy, served from Go, not a PHP worker. Production is a 2 GB box: note its memory before and after the first deploy, and check that the host Caddy streams `/.well-known/mercure` without buffering.
- Each stack's `php` container runs its own hub. `make up-prod-live`, `make up-prod-playground` and `make up-prod-local` refuse to start unless the env file sets `MERCURE_JWT_SECRET` (32+ characters), because FrankenPHP cannot parse its Caddyfile without it.

## Alternatives Considered

- **Publishing in the sync listener.** One hub call per event, inside the request or the scheduled job, with duplicates left to Turbo's 150 ms debounce in each browser.
- **Making the events async**, as the skeleton did. Changes when the Audit, Pricing and badge listeners run.
- **Collecting order IDs in memory during the flush** and dispatching once at the end. Needs a hook for the end of each flush and `ResetInterface` for worker mode; Messenger's deduplication does it with no state in a service.
- **Redis for every lock** (`LOCK_DSN`), with no service override. A Redis lock expires after its TTL, and `LockableTrait` sets the default 300 s with no refresh, so a backup or prune running longer would lose its lock part-way; flock holds it until the process ends. The rate limiter's locks would also become a Redis call per request.
- **Public topics and `#[Broadcast]` on entities.** Puts UI in `Domain/` and broadcasts every Doctrine change rather than domain events.
