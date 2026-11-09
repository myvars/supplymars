---
title: Reviews and a support desk
period: January – February 2026
summary: Two new bounded contexts in two weeks. Product reviews with moderation and an eligibility rule, and a support desk built on pools that staff choose to work.
refs:
  - label: ADR 009 · Support ticket system
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/009-support-ticket-system.md
  - label: Architecture
    url: https://github.com/myvars/supplymars/blob/main/Docs/03-architecture.md
---

With the move to bounded contexts merged in January, adding a feature meant adding a context: its own domain model, commands, handlers, repository interfaces and screens, using other contexts only through their domain. Reviews and the support desk were the first two built that way from scratch, two weeks apart.

## Reviews

A shop without reviews is missing the most-read text on a product page. The Review context gives each product customer reviews, moderated by staff.

**Who may review.** A customer can review a product only if they have a delivered order containing it, and only once per product. That rule started as a validator in the Review context, asking the Order context's repository for the delivered order rather than reaching into Order's code.

**Moderation as a state machine.** A review is pending, published, hidden or rejected, and the status enum says which moves are allowed. Pending can be published or rejected. Published can be hidden and hidden can be republished, so staff can take a review down without losing it. Rejected is final. Each move is its own command and handler: approve, reject, hide and republish, plus create, update and delete.

**Summaries kept by events.** A product page shows a review count, an average rating and the spread across ratings. Computing those on every page view would mean aggregating every review each time. Instead, `ReviewSummaryUpdater` listens for review events and keeps a `ProductReviewSummary` row up to date, so the page reads one row.

**Data to moderate.** A generator command writes pending reviews for delivered orders, so the moderation queue can be filled on demand for a demo or a test.

## A support desk

Staff need a way to work through customer requests together. The Note context is an internal support desk: staff log tickets for customers and work them, and the model already has a customer side. It has three entities: pools, tickets and messages.

**Pools that staff choose.** A pool is a category of work, such as billing or returns. Staff subscribe to the pools they want to work, and see those tickets in their queue. ADR 009, written a few days later, records why that beat the alternatives: a flat list with a dispatcher, which makes one person the bottleneck; tags, which have no clear owner; and round-robin assignment, which ignores who knows what. Tickets move between pools as an issue becomes clearer.

**Status from the conversation.** A ticket is open, replied or closed, and replies move it. A staff reply sets it to replied, because the customer has an answer. A customer reply sets it back to open. System messages change nothing. Staff close a ticket explicitly. In the common case nobody manages status by hand.

**Snooze as a timestamp.** Snoozing is a `snoozedUntil` date on the ticket, not another status. A ticket can be open and snoozed, or replied and snoozed, and drops out of the queue until the date passes. One field instead of a set of extra statuses and the transitions between them.

**Internal notes.** Each message is public or internal, so staff can discuss a ticket on the ticket itself, and internal notes stay internal from customers.

**Fast lists.** A ticket stores its message count and the time of its last message, updated as messages are added or removed. The queue, the most-viewed page, reads them without touching the messages table, backed by an index on pool, status and snooze date.

## FormFlow grew with it

The support desk pushed the controller pattern forward. Route names in templates had been built by joining strings; FormFlow now passes a typed `FlowRoutes` object, and controllers describe their model with a `FlowModel` value object instead of a string constant. And the ticket page needed a reply form inside it, not in a modal, so FormFlow gained embedded forms.
