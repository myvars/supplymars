---
title: From admin tool to product
period: April – June 2026
summary: A dashboard that answers "what should I do next?", demo orders anyone can place, help on every page, then Symfony 8.1 with its deprecations cleared.
refs:
  - label: User manual
    url: https://github.com/myvars/supplymars/blob/main/Docs/UserManual/README.md
---

With V1.0 public, the people signing in were no longer me. They were visitors to the playground, opening a back-office tool they had never seen. The first week of April went on making SupplyMars explain itself.

## A dashboard that says what to do next

Signing in to the playground used to land you on the product list. That doesn't answer the question an operations user starts the day with: what needs me?

The new dashboard at `/dashboard/` does. Today's orders, revenue, profit and margin, each against the same day last week. A list of things needing attention: pending and overdue orders, rejected purchase orders, and reviews waiting for moderation. The latest orders with their status, the top products, and links to orders, customers, products and categories with their counts.

I kept it separate from the marketing home page on purpose. A visitor who signs in to the playground can still find the home page and the About page, and the reports moved to their own address, `/reports/`. Soon after, I removed an N+1 query from the latest-orders list; it now loads each order's customer in the same query.

## Demo orders

The simulation creates orders on its own schedule, which is no use to a visitor who wants to see an order arrive now. The order list has a **Create Demo Order** button. It uses the same code as the simulation, pulled out of the console command into a `DemoOrderCreator` service, so a demo order is a real order in every way. A global daily limit stops anyone flooding the playground, counted by the `DEMO-` prefix on the order reference. New orders glow briefly when they appear, so the visitor sees which one is theirs.

At the same time, creating an order by hand, field by field, became a super admin action.

## Help on every page

A **Help** button in the header opens a side panel with help for the page you are on: twenty pages, from sign-in to the pricing screens, loaded through a Turbo Frame. Ten form fields where the label alone isn't enough got help text: the price model, the markup cascade, staff access, ticket pool visibility. The user manual gained chapters on manufacturers and support tickets, and caught up with the dashboard, demo orders and the product tabs.

## Symfony 8.1

April and May were upkeep: dependency and recipe updates, and a fix so the playground's nightly reset jobs receive their environment.

At the start of June I moved to Symfony 8.1 and cleared every deprecation it reported. The suite was clean on 8.1, which set up the next job: moving FormFlow into its own package.
