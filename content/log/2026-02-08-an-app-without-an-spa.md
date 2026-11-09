---
title: An app without an SPA
period: 3 – 8 February 2026
summary: Modals, inline editing and native swipe-back on iPhone, all on server-rendered pages with Turbo. Plus PO Rewind, for undoing a purchase order.
figures:
  - value: "1"
    label: template that renders as a modal or a page
  - value: "7"
    label: record types with inline editing
  - value: "0"
    label: client-side state to keep in step
refs:
  - label: ADR 007 · Turbo frames and modals
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/007-turbo-frame-modal-architecture.md
  - label: ADR 008 · Server-driven UI
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/008-server-driven-ui-architecture.md
  - label: Turbo pattern docs
    url: https://github.com/myvars/supplymars/blob/main/Docs/patterns/Turbo/README.md
---

SupplyMars renders every page on the server. There is no React and no client-side router; the screens are HTML from the server. Hotwire Turbo makes navigation feel instant by swapping in HTML, and small Stimulus controllers handle the rest. This week was about the details that decide whether that approach feels like an app or like a website: modals, editing in place, and the back gesture on an iPhone. ADR 007 and ADR 008 record the design.

## One template, modal or page

Modals in SupplyMars are native `<dialog>` elements with a Turbo Frame inside. A link with `data-turbo-frame="modal"` loads its page into that frame, and a Stimulus controller opens the dialog when the content arrives.

The useful part is on the server. `modal_base.html.twig` checks the request's `Turbo-Frame` header: if the request came from the modal frame, it renders just the content; otherwise it renders the full page around it. So every form works both ways from one template. Open "Edit product" from a list and it is a modal; open the same URL in a new tab and it is a page. Nothing is duplicated, and every modal has a real URL.

After a form saves, the response tells Turbo what to do next: a stream that refreshes the page behind the modal when the request came from a frame, a normal redirect otherwise. The page underneath refreshes in place, keeping the user's scroll position.

The frame layout became a short, fixed hierarchy: `body` for the main content, `modal` for dialogs, a frame per results table so searching and paging don't reload the page, and one for the reports. Each has one job and its URL behaviour is written down.

## Editing in place

Small changes, like renaming a category, shouldn't need a form in a modal. Inline editing turns a value on the page into an input in place. Save, and the field swaps back to its new value through a self-replacing Turbo Frame.

It is built as part of FormFlow, the project's controller pattern: `InlineEditFlow::handleField()` takes the current value, a callback that applies the new one, the frame and template to render, and the validation constraints. A toast arrives as a second Turbo Stream, and only when the value actually changed: Doctrine's change tracking decides, so saving the same value shows nothing. After a trial on one screen it went out across categories, subcategories, manufacturers, suppliers, products, VAT rates and, a week later, customer names.

## The swipe-back gesture

Turbo takes over browser navigation, and iOS Safari has its own idea of what going back should look like. Swiping back on an iPhone showed a stale page, or animated twice.

The first fix, that morning, re-fetched the page whenever Safari restored it from its back-forward cache. By the afternoon I had replaced it with one aimed at the actual conflict: Safari 18 animates the swipe itself, and that animation can't be turned off, while Turbo was also running its own view transition on the same navigation. The shipped fix removes the re-fetch, detects Safari on iPhone and iPad, and turns off Turbo's view transition for back and forward visits only, turning it back on once the page has rendered. Ordinary link navigation keeps its transition, and the swipe feels native.

## PO Rewind

The simulation occasionally leaves a purchase order in a state a human needs to undo: some items accepted, some rejected, the order half-sourced. PO Rewind puts a purchase order and its items back to pending, so it goes through the supplier's accept or reject step again.

A rewind removes the status log entries it reverses, so the timeline shows the order's actual path, and then re-derives the customer order's status from its items. A console command, `app:rewind-mixed-status-purchase-orders`, finds purchase orders whose items have mixed statuses including a rejection, with a `--dry-run` to see what it would change first. It shipped with about 880 lines of tests.

*Update, October 2026: a rewind now refuses a purchase order with refunded or cancelled items. A refunded quantity has usually been re-sourced elsewhere, so rewinding it could put the same quantity on two purchase orders.*
