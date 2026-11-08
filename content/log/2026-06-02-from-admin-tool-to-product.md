---
title: From admin tool to product
period: April – June 2026
summary: A dashboard that answers "what should I do next?", demo orders anyone can place, help on every page, then Symfony 8.1 with its deprecations cleared.
figures:
  - value: "19"
    label: contextual help pages
  - value: "10"
    label: form fields given help text
  - value: "0"
    label: Symfony 8.1 deprecations left
refs:
  - label: User manual
    url: https://github.com/myvars/supplymars/blob/main/Docs/UserManual/README.md
  - label: Dashboard user guide
    url: https://github.com/myvars/supplymars/tree/main/Docs/UserManual
---

With V1.0 public, the people signing in were no longer me. They were visitors to the playground, opening a back-office tool they had never seen. The first week of April went on making SupplyMars explain itself.

## A dashboard that says what to do next

Signing in used to land you on the marketing home page or a reporting overview. Neither answers the question an operations user starts the day with: what needs me?

The new dashboard at `/dashboard/` does. Today's orders, revenue, profit and margin, each against the same day last week. A list of things needing attention: pending and overdue orders, rejected purchase orders, and reviews waiting for moderation. The latest orders with their status, the top products, and links into each part of the system with live counts.

I kept it separate from the marketing home page on purpose. A visitor who signs in to the playground can still find the home page and the About page, and the reports moved to their own address, `/reports/`. The next day I removed an N+1 query from the latest-orders list; it now loads each order's customer in the same query.

## Demo orders

The simulation creates orders on its own schedule, which is no use to a visitor who wants to see an order arrive now. The order list has a **Create Demo Order** button. It uses the same code as the simulation, pulled out of the console command into a `DemoOrderCreator` service, so a demo order is a real order in every way. A global daily limit stops anyone flooding the playground, counted by the `DEMO-` prefix on the order reference. New orders glow briefly when they appear, so the visitor sees which one is theirs.

At the same time, creating an order by hand, field by field, became a super admin action.

## Help on every page

A **Help** button in the header opens a side panel with help for the page you are on: nineteen pages, from sign-in to the pricing screens, loaded through a Turbo Frame. Ten form fields where the label alone isn't enough got help text: the price model, the markup cascade, staff access, ticket pool visibility. The user manual gained chapters on manufacturers and support tickets, and caught up with the dashboard, demo orders and the product tabs.

Two fixes came out of the polish. The help panel was fetching its content again on every open, because a Turbo Frame's `src` becomes an absolute URL after the first load and my comparison used the raw attribute; it now compares parsed URLs. And the date picker's stylesheet carried its own copy of Tailwind's utility layer, which overrode responsive classes like `hidden sm:inline`; it is now imported into the single Tailwind build, before Tailwind itself.

## Symfony 8.1

April and May were upkeep: dependency and recipe updates, and a fix so the playground's nightly reset jobs receive their environment.

At the start of June I moved to Symfony 8.1 and cleared every deprecation it reported rather than leaving them for later:

- Named autowiring aliases selected by `#[Target]` instead of by parameter name, for the Flysystem storages.
- The public ID resolvers indexed with `#[AsTaggedItem]` instead of a default index method.
- `eraseCredentials()` marked `#[\Deprecated]`, the email verifier on its newer method, and a no-op Doctrine option removed.
- `config/reference.php`, which Symfony regenerates, no longer tracked in git, so it stops cluttering every diff.

The suite was clean on 8.1, which set up the next job: moving FormFlow into its own package.
