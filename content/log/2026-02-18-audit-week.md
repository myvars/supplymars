---
title: Audit week
period: 13 – 18 February 2026
summary: I ran a structured audit of my own codebase, then worked through it in a week. Backups, failure handling, a deny-by-default firewall, money in exact arithmetic, and an interface pass that cut 15 icon sets to 3.
refs:
  - label: ADR 009 · Support tickets
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/009-support-ticket-system.md
  - label: ADR 010 · Customer insights
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/010-customer-insights-reporting.md
  - label: Runbook
    url: https://github.com/myvars/supplymars/blob/main/Docs/11-runbook.md
---

By mid-February SupplyMars had ten bounded contexts, documentation for most of them and PHPStan at level 7. It was a good moment to find out what I couldn't see from the inside.

I ran a structured audit with Claude Code, set up as a panel of five reviewers, each with one job: a PHP and Symfony architect, a DDD specialist, a UI and UX reviewer, a test engineer and a pragmatic DevOps engineer. Each reviewed the codebase from its own angle and checked it against the docs. The report came back with strengths and risks, and fifteen recommendations.

The strengths were the ones I had worked for: a consistent architecture applied the same way across every context, controllers of five to eight lines per action, docs that matched the code, and PHPStan level 7 with nothing in its baseline. The risks were mostly operational, and they were right.

## The operational gaps

**No automated backups.** That was the first risk on the list, and it went first. A backup command now dumps the production database, uploads it to S3 every night, and can restore it in development.

**Failures that nobody would see.** The simulation's console commands processed records in loops, and an exception inside a loop stopped the run without a trace. Every loop now catches, logs with context and carries on, so one bad record can't stop the batch and every failure is visible.

**Jobs in the wrong order.** The cron schedule accepted purchase orders before building them. Building now runs first.

**Sign-in protection.** Symfony's login throttling went on, five attempts in fifteen minutes, with rate limits on the authentication endpoints in front of it.

**A runbook.** Deployment, health checks and troubleshooting, written down before I needed them rather than during an incident.

## Keeping the seam

One recommendation asked me to decide about the async domain event infrastructure, an interface and dispatch logic that nothing used yet: put it to work, or remove it. I kept it, and recorded why: it was a deliberate seam for events that would later need the queue. An audit is input to decisions, not a list of orders, and the report recorded my choice beside its recommendation. Others were deferred or marked won't fix, each with its reason, including centralised logging, which the report rated as a high production gap.

## The follow-up round

A second round of findings went in the next day:

- **Deny by default.** Access used to be checked controller by controller, with an attribute on each. Now the firewall lists what is public (the home page, sign-in, password reset, the About pages) and everything else requires a staff role. A new page is private unless I say otherwise.
- **Money in exact arithmetic.** A few totals were still summed with floating point. Order and purchase order totals now use `bcmath` on decimal strings, like the pricing engine always had, and a product price comparison uses `bccomp`.
- **A Content Security Policy**, with the reasons for each exception written down. The first version blocked Asset Mapper's import map shims and the product images on S3; I widened exactly those two the next day.
- **A post-deploy smoke test**, cron output into the container logs with rotation, and ADR 010 for Customer Insights.
- **OPcache preload on PHP 8.5**, which shipped a day later. PHP 8.5's preloader crashed PHP-FPM when it walked the full dependency tree of 900-plus classes. Preloading the compiled container with plain `require` statements kept most of the benefit, so I shipped that and baked the cache into the image at build time.

  *Update, October 2026: preload is off. Under FrankenPHP's worker mode the app stays loaded anyway, and preload caused [an incident after worker restarts](/log/worker-mode).*

## The interface pass

A separate UI audit followed, in two rounds, and around twenty commits over the following two days worked through it in phases:

- **Consistency.** Icons from twelve sets were replaced with Bootstrap Icons, leaving three sets in use. Dark-mode borders and dividers were standardised. Cards, badges and alerts got one set of rules.
- **Accessibility.** Labels on icon-only buttons and a skip link had gone in with the first audit. This pass moved buttons to `focus-visible` outlines and gave the order and purchase order pages a real `h1` instead of a styled `h5`.
- **Components.** A breadcrumb in place of ad-hoc back links, a `DataTable` component extracted from the reports, an action slot on empty states, and error pages with a clear code and a way back.
- **Stimulus**, two days later. Shared chart formatting and Turbo refresh helpers extracted, two controllers merged, and one with no references deleted.

The week ended with the report closed: every item fixed, deferred with a reason, or recorded as already handled.
