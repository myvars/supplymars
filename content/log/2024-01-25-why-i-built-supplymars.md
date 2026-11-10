---
title: Why I built SupplyMars
period: January 2024
summary: A few small tools to try the way PHP is written now, which slowly hardened into a full e-commerce operations suite. Where it started, what I wanted to learn, and how a part-time project kept one shape.
refs:
  - label: About the developer
    url: https://www.supplymars.com/about
  - label: Overview
    url: https://github.com/myvars/supplymars/blob/main/Docs/01-overview.md
  - label: Architecture
    url: https://github.com/myvars/supplymars/blob/main/Docs/03-architecture.md
---

In 2000 I wrote Ebuyer's first website, and the tools the business ran on behind it, on a LAMP stack: PHP, MySQL and whatever I built myself. The [About page](https://www.supplymars.com/about) has the full account. I kept coding after I left in 2014, and kept up with how the work changed. By 2024 almost every part of it had.

## Then and now

The way PHP applications are built has changed a great deal since then. The big differences:

- **Frameworks**, such as Symfony, that provide the routing, services, forms and security every application once built for itself.
- **A stricter language**: strict types, enums, readonly properties and attributes.
- **Composer**, so code is shared as versioned packages.
- **Rich entities and domain-driven design**, with an ORM mapping the domain model to the database.
- **Automated tests**, run by CI on every change before it ships.
- **Containers**, so development, CI and production run the same way.

I had used most of it on my own projects. What I hadn't had was a codebase that used all of it from the first line, rather than modern practice added a piece at a time to something older. SupplyMars was that clean sheet.

## A sandbox, not a shop

The first commit, on 25 January 2024, wasn't a shop. It was a skeleton called Butterfly: Symfony 7.0 on PHP 8.3, sign-up and login, Tailwind, and Turbo and Stimulus loaded through Asset Mapper, with no Webpack and no bundler. What I wanted to try was small and specific:

- **Server-rendered pages that feel like an app.** Modals, instant navigation and parts of a page updating in place, with no JavaScript framework and no second copy of the logic in the browser.
- **Symfony as it is written now.** Attributes in place of configuration files, autowiring, Twig components, and forms and validation doing the heavy lifting.
- **Tests from the start**, and a way of working where a change is checked before it ships.
- **A domain model that owns its rules**, not one that services work around.

E-commerce was the obvious domain to build it on. I had spent fifteen years in it, so I already knew the problems: products with several suppliers, prices that move, orders that split, and the staff tools that keep all of it running. My attention could go on how to build, not on what.

## The design

Starting from a clean sheet, the design could begin with rich entities that hold their own rules, and with domain-driven design. It went further a step at a time:

- **Rich entities.** Status enums that decide their own transitions, and entities that answer questions services used to work out from their fields (2024).
- **Domain events**, raised by the entity that changed (2024), later rewritten as small, immutable facts (2025).
- **Named constructors and private setters**, so an order can only be built whole (2025).
- **Value objects and typed IDs**, so a purchase order's ID can't be passed where an order's is expected (2025).
- **Readonly commands and handlers**, in bounded contexts with rules between them (2026), checked in CI since October 2026.

## Part-time, and rewritten often

SupplyMars was never a full-time job. Some weeks I could give it several days, others an evening or two, and some months nothing at all.

Coming back after a gap meant reading my own code with fresh eyes, and I raised it to what I had learned since. Much of the work was deliberate rework. The shared code behind the admin screens went through [four versions in four months](/log/evolving-a-crud-abstraction), and was later replaced by FormFlow, which [became a package](/log/formflow-becomes-a-package). The test suite was rebuilt from scratch once and reworked again. A string of commits in 2024 are simply called "Code kaizen": small, continuous improvements, aimed at one consistent style across the whole codebase rather than a different style for each month it was written in.

## How a sandbox hardened

The tools grew into a business one piece at a time:

- **2024.** A catalogue with a pricing engine and suppliers, then customer orders, purchase orders and a status tree, domain events and an audit trail. In September a simulation started running the business on its own. In October the project was rebranded as SupplyMars, a shop for life on Mars, with a new theme and 250 AI-generated product images, and in November came the first sales reports.
- **2025.** A test for every class, Docker and a CI pipeline, Redis, and the start of the rebuild by bounded context.
- **2026.** Documentation and ADRs, product reviews, a support desk, customer insights, a REST API, a public playground and the V1.0 release in March, then FrankenPHP in worker mode, live pages with Mercure, and architecture rules the build enforces.

Somewhere along the way it stopped being a sandbox. Once the site was public and the simulation ran day and night, it needed what any production system needs: backups that had been restored at least once, a pipeline that checks every change, error tracking, and rules the code actually follows.

## This log

The posts that follow tell that story, by period of work and by feature: the decisions, the trade-offs and what went wrong. Most were written in November 2026 from the commit history and the pull requests, so the figures come from the code, and the reasons come from me.
