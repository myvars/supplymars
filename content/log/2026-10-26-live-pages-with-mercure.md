---
title: Live pages with Mercure
period: 23 – 26 October 2026
summary: Order and purchase order pages that update themselves as the simulation runs, built on server-rendered HTML, with a security model that says exactly what a subscriber can learn.
refs:
  - label: ADR 017 · Live updates with Mercure
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/017-live-updates-with-mercure.md
  - label: "#117 · Live order page"
    url: https://github.com/myvars/supplymars/pull/117
  - label: "#121 · Live purchase order page"
    url: https://github.com/myvars/supplymars/pull/121
  - label: "#124 · Refresh on edits, removals, rewinds"
    url: https://github.com/myvars/supplymars/pull/124
  - label: "#145 · Public refreshes, private toasts"
    url: https://github.com/myvars/supplymars/pull/145
  - label: "#133 · One origin for the hub"
    url: https://github.com/myvars/supplymars/pull/133
---

SupplyMars runs a simulated business around the clock. Purchase orders are accepted four times an hour, items ship and arrive every hour, and other staff lock and edit orders. Until this week, someone looking at an order saw none of that until they reloaded. For an operations tool, a stale page is a wrong page.

I wanted live pages without giving up what makes this app simple to work on: server-rendered Twig, Turbo, and no client-side state to keep in step. FrankenPHP has a Mercure hub built in, so the server can push to the browser with no extra service. I proved the pattern in my smaller skeleton project first, then brought it across ([#117](https://github.com/myvars/supplymars/pull/117)).

## A refresh, not a payload

The page subscribes to a topic for its order. When the order changes, the server publishes a refresh, and Turbo reloads the page and morphs the new HTML into the old. No JSON, no client-side rendering, no second copy of the page's logic in JavaScript. The page that arrives is the same one a reload would give, through the same controller and the same access check.

Morphing keeps the viewer's place, but it also replaces whatever they have open, so anything that can be open protects itself and keeps its state through a refresh. I tested it with an Add Item modal open, text typed into it, while the order changed underneath.

## One refresh per order, from the worker

One database flush in the simulation can touch an order dozens of times: every item, every purchase order item, every status change raises a domain event. Publishing one refresh per event would hammer the hub and every viewer.

A listener, `RefreshOrderPageForViewers`, turns those events into one queued `RefreshOrderPage` message per order, using Messenger's `DeduplicateStamp`. The worker publishes it, so the request that made the change never waits on the hub, and a hub failure is logged, never thrown.

Deduplication needs a lock that both containers can see, which the project's `flock` locks can't be. So it has its own lock store in Redis, used only by Messenger's deduplication; every other lock stays on `flock`, where a long-running backup can't outlive its lock.

## Finding the events that weren't there

A live page is a hard test of a domain model. If a change raises no event, the page doesn't move, and you see it immediately.

Building this turned up four events that existed in the code but were never raised, and changes to purchase order items and rewinds that raised none. I raised the missing ones and added events for the others ([#124](https://github.com/myvars/supplymars/pull/124)).

The purchase order page went live the day after the order page ([#121](https://github.com/myvars/supplymars/pull/121)), by subscribing to its order's topic. The topic name lives on the `CustomerOrder` aggregate, which both the Order and Purchasing contexts may reach under the architecture rules, rather than being built in two places.

## Toasts for the person who needs to know

Live pages help whoever is looking. Some changes matter to someone who isn't. If you hold the lock on an order and another staff user releases or takes it, you should hear about it on whatever page you're on.

`CustomerOrder::lockOrder()` raises `OrderLockWasChangedEvent`, carrying only IDs. A handler sends the previous holder a toast, "Sam Carter released your lock on order #000003", with a link, but only when someone else changed the lock. Releasing your own lock sends nothing. In the browser the toast arrived on the order list about half a second after the click.

## Public refreshes, private toasts

The first version made every update private. Each page response set a subscriber cookie granting the user's own topic plus that page's order topics.

Testing it against real browsing showed the flaw in that design. The browser holds one Mercure cookie, and every response replaces it. A page opened in another tab, or any other response, replaced the grant, and after an hour the hub refused the token anyway. Either way, the order page went quiet at its next reconnect. On `main`, with a second tab open, a lock change produced nothing in ten seconds.

So I separated the two kinds of message by what they carry ([#145](https://github.com/myvars/supplymars/pull/145)):

- **A refresh is public and carries no data.** It says "this order changed", nothing more, and each viewer's reload goes through the page's own staff-only access check.
- **A toast is private**, on the user's own topic, and the cookie grants only that topic, the same on every page.

SupplyMars has customers who can register and sign in, so the cookie is staff only; otherwise a customer could subscribe to every order topic and learn when orders change.

ADR 017 records the guarantee in one line: any browser holding a token can learn *when* an order changes, never *what* changed, and no content ever goes on a page topic. Staff can see every order anyway. In production the hub lives on `www`, so the bare domain now redirects there, rather than have the Content Security Policy block a cross-origin subscription ([#133](https://github.com/myvars/supplymars/pull/133)).

## How it was proved

The tests cover each kind of change and every case where nothing should be sent; with deduplication or the "someone else" check removed, seven of them fail.

In the browser, with two staff users side by side, an order went from Processing to Accepted, Shipped and Delivered while the simulation commands ran, without a reload.

The deploy itself returned a 500 on every page once a worker restarted: a fault in OPcache preload that Mercure's services exposed. [FrankenPHP in worker mode](/log/worker-mode) has the trace and the fix.
