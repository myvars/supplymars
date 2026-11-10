---
title: Audit week
period: 13 – 18 February 2026
summary: I ran a structured audit of my own codebase, then worked through it in a week. Backups, failure handling, a deny-by-default firewall, money in exact arithmetic, and an interface pass that cut 15 icon sets to 3.
refs:
  - label: ADR 010 · Customer insights reporting
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/010-customer-insights-reporting.md
  - label: Runbook
    url: https://github.com/myvars/supplymars/blob/main/Docs/11-runbook.md
---

By mid-February SupplyMars had ten bounded contexts, documentation for most of them and PHPStan at level 7. It was a good moment to find out what I couldn't see from the inside.

I ran a structured audit with Claude Code, set up as a panel of five reviewers, each with one job: a PHP and Symfony architect, a DDD specialist, a UI and UX reviewer, a test engineer and a pragmatic DevOps engineer. Each reviewed the codebase from its own angle and checked it against the docs. The report came back with strengths and risks, and fifteen recommendations.

The strengths were the ones I had worked for: one architecture applied the same way in every context, thin controllers, docs that matched the code, and PHPStan level 7 with an empty baseline. The risks were mostly operational, and they were right.

## The operational gaps

Top of the list: nothing backed up the database. That went first. A backup command now dumps the production database, uploads it to S3 every night, and can restore it in development.

The second was failures nobody would see. The simulation's console commands processed records in loops, and an exception inside a loop stopped the run without a trace. Every loop now catches, logs with context and carries on, so one bad record can't stop the batch and every failure is visible.

The rest were smaller. The cron schedule accepted purchase orders before building them; building now runs first. Sign-in got Symfony's login throttling, with rate limits on the authentication endpoints in front of it. And a runbook covers deployment, health checks and troubleshooting, written down before I needed it rather than during an incident.

## Keeping the seam

One recommendation asked me to decide about the async domain event infrastructure, an interface and dispatch logic that nothing used yet: put it to work, or remove it. I kept it, and recorded why: it was a deliberate seam for events that would later need the queue. An audit is input to decisions, not a list of orders, and the report recorded my choice beside its recommendation. Others were deferred or marked won't fix, each with its reason.

## The follow-up round

A second round of findings went in straight after:

- **Deny by default.** Access used to be checked controller by controller, with an attribute on each. Now the firewall lists what is public (the home page, sign-in, password reset, the About pages) and everything else requires a staff role. A new page is private unless I say otherwise.
- **Money in exact arithmetic.** A few totals were still summed with floating point. Order and purchase order totals now use `bcmath` on decimal strings, like the pricing engine always had, and a product price comparison uses `bccomp`.
- **A Content Security Policy**, with the reasons for each exception written down. The first version blocked Asset Mapper's import map shims and the product images on S3, and I widened it for those two soon after.

Alongside them went a post-deploy smoke test, cron output into the container logs, ADR 010 for Customer Insights, and OPcache preload, later turned off under worker mode ([the incident](/log/worker-mode)).

## The interface pass

A separate UI audit followed, in two rounds, and over the following days I worked through it in phases. Consistency came first: fifteen icon sets came down to three, with Bootstrap Icons replacing the other twelve, and cards, badges, alerts and dark-mode borders got one set of rules. Accessibility next: buttons moved to `focus-visible` outlines, and the order and purchase order pages got a real `h1` instead of a styled `h5`. Then components: a breadcrumb in place of ad-hoc back links, a `DataTable` extracted from the reports, and error pages with a clear code and a way back. The Stimulus controllers were last, with shared helpers extracted, two controllers merged and an unused one deleted.

The week ended with the report closed: every item fixed, deferred with a reason, or recorded as already handled.
