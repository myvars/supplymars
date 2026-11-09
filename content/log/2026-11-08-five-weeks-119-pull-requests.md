---
title: Five weeks, 119 pull requests
period: 6 October – 8 November 2026
summary: A new PHP runtime, pages that update themselves, architecture rules enforced in CI, and two production incidents, found and fixed at the cause.
figures:
  - value: "119"
    label: pull requests merged
  - value: "93"
    label: issues closed
  - value: "5"
    label: new ADRs (013–017)
  - value: 54s → 22s
    label: CI test step
  - value: ~1.7×
    label: throughput on a light page
  - value: 453 → 144 MiB
    label: MySQL memory, idle
refs:
  - label: ADR 013 · Database queue
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/013-database-queue-by-default.md
  - label: ADR 014 · Architecture rules
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/014-architecture-rules.md
  - label: ADR 015 · Scheduler
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/015-symfony-scheduler-in-place-of-cron.md
  - label: ADR 016 · FrankenPHP
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/016-frankenphp-in-place-of-nginx-and-php-fpm.md
  - label: ADR 017 · Mercure
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/017-live-updates-with-mercure.md
  - label: All pull requests
    url: https://github.com/myvars/supplymars/pulls?q=is%3Apr+is%3Amerged
---

In October I set out to give SupplyMars the work a production system needs and a demo usually skips: a modern runtime, a pipeline that checks everything, rules that the code actually follows, backups I had restored at least once, and a test suite I trusted.

I also tightened how I work. Every change started as a GitHub issue, went onto its own branch and landed as a pull request that says what changed, how it was tested, what to look at closely and what was left out. Five weeks later there were 119 of them. This post is the story of those five weeks, with the numbers from the PRs.

## How the weeks went

| Week | What it was about |
|---|---|
| 6–12 Oct | Dependencies and tooling refreshed |
| 13–19 Oct | The Shadcn UI kit in place of Flowbite, CI gaps closed, images built in CI, Deptrac rules, a database queue, Sentry and `/health`, hashed API tokens, parallel tests, streamed backups |
| 20–26 Oct | The Scheduler in place of cron, FrankenPHP and then worker mode, MySQL sized for a 2 GB server, a docs review, live pages with Mercure |
| 27 Oct – 2 Nov | The bugs the docs review found, a rehearsed restore, about 20 PRs reworking the tests |
| 3–8 Nov | FormFlow 1.6, the Symfony 8.1.11 security release, a sturdier playground reset |

## A new runtime: FrankenPHP in worker mode

The site ran on nginx and PHP-FPM. I replaced both with one FrankenPHP image ([#81](https://github.com/myvars/supplymars/pull/81)), then moved production to worker mode ([#85](https://github.com/myvars/supplymars/pull/85)), where the Symfony kernel stays loaded between requests. Everything nginx used to do moved into the app, so development, tests and production behave the same, and a new rule, guarded by a test, keeps request data out of services that now live across requests. The home page handled 1.5–1.7× as many requests under load, and memory stayed flat at about 200 MiB over 25,000 requests. [FrankenPHP in worker mode](/log/worker-mode) has the benchmarks and the details.

## Pages that update themselves

With Mercure ([#117](https://github.com/myvars/supplymars/pull/117), [#121](https://github.com/myvars/supplymars/pull/121)), order and purchase order pages refresh themselves as the simulation moves them, and if someone releases an order lock you hold, you get a toast with a link. A refresh is public and carries no data; a toast is private, and only staff can subscribe ([#145](https://github.com/myvars/supplymars/pull/145)). Building it turned up four domain events that existed in the code but were never raised ([#124](https://github.com/myvars/supplymars/pull/124)). [Live pages with Mercure](/log/live-pages-with-mercure) tells the whole story.

## Architecture rules the code actually follows

The architecture docs described rules that nothing checked, and the code had drifted from them. Unchecked rules are worse than none, so I made them checkable. ADR 014 rewrote them to match a design I actually wanted: a context may use another context's domain (its entities, events and repository interfaces) but never its handlers, services or controllers, and the shared kernel imports no business context. Deptrac checks it in CI ([#37](https://github.com/myvars/supplymars/pull/37)).

Measured against the new rules, 50 imports broke them. I fixed 45 by moving or simplifying code. The other five became named exceptions, each its own Deptrac layer that only the context that needs it may use. There is no `skip_violations` list, and a catch-all layer fails the build for any context that isn't registered.

## The Scheduler in place of cron

Seventeen cron jobs, two crontab files and a cron container became one PHP class, `MainSchedule`, run by the Messenger worker ([#79](https://github.com/myvars/supplymars/pull/79)). The worker runs one job at a time, which removes a problem cron couldn't: two jobs due in the same minute ran together, and the logs showed about 11 database deadlocks a day. With one job at a time, they stopped.

The schedule is stateful and locked, so a missed run happens once after a restart, and a failed job reaches Sentry instead of disappearing into a container log. The demo-order command used to sleep for up to five minutes between orders to spread them out. Jitter on the schedule does that now.

## A smaller, simpler stack

RabbitMQ was carrying nothing but outgoing email, through a third-party transport below version 1.0, and the test suite needed a running broker. I moved the `async` transport to Symfony's own Doctrine transport ([#23](https://github.com/myvars/supplymars/pull/23)); tests use an in-memory one that they can inspect, and RabbitMQ is still there behind a Compose profile if I ever need it. Switching back is a config change. ADR 013 has the reasoning.

## A pipeline that checks everything

CI used to run code style, PHPStan, a mapping check and the tests. Now it runs `make check`, step by step: PHP-CS-Fixer, Rector, PHPStan level 7, Deptrac, the Symfony linters, `composer audit`, migrations built from an empty database and compared with the entities, the tests and the production config ([#14](https://github.com/myvars/supplymars/pull/14)). I run the same commands locally.

CI also builds the production images and pushes them to GHCR, tagged with the commit SHA ([#31](https://github.com/myvars/supplymars/pull/31)). The server pulls the image for the commit it has checked out and never builds one, so a rollback is a `git reset` and a pull.

## Security

- Every action that changed state through a GET link became a one-button POST form with a CSRF token ([#19](https://github.com/myvars/supplymars/pull/19)).
- API tokens are stored as SHA-256 hashes, issued by a console command and shown once ([#30](https://github.com/myvars/supplymars/pull/30)).
- Security headers and HSTS come from the app; sign-in, registration and password reset are rate-limited; image uploads are checked on the server; Redis needs a password.
- Six rules were checked only by a form or a template, so an inline edit, the API or a hand-made POST could skip them. Each is now checked where the change is made, which brought in the project's first security voter ([#115](https://github.com/myvars/supplymars/pull/115)). Edit forms take a record's ID from the route, never from a hidden field ([#166](https://github.com/myvars/supplymars/pull/166)).
- Advisories got patched quickly: two Symfony component CVEs, PHP 8.5.12 and Symfony 8.1.11 ([#215](https://github.com/myvars/supplymars/pull/215)).

## Operations on a 2 GB server

Production runs two stacks, live and the playground, on one small server, so memory matters. I tuned MySQL, turned off binary logging and started pruning old status history every night ([#90](https://github.com/myvars/supplymars/pull/90), [#92](https://github.com/myvars/supplymars/pull/92)):

| Measure | Before | After |
|---|---|---|
| MySQL memory, idle | 453 MiB | 144 MiB |
| MySQL memory, with a copy of live | 616 MiB | 356 MiB |
| Status history rows | 5.28 M | 3.04 M |
| Whole database | 1,713 MB | 1,174 MB |
| Nightly dump, compressed | 139 MB | 114 MB |

The backup command used to read the whole dump into memory. At about 137 MB compressed and growing, it would soon have failed every night. Upload and restore now stream, and a short download is caught and deleted instead of restored ([#49](https://github.com/myvars/supplymars/pull/49)). Then I rehearsed the runbook's restore on the playground stack, checked it table by table, and wrote down how to repeat it every month ([#162](https://github.com/myvars/supplymars/pull/162)). A backup is only proven once it has been restored.

`/health` runs one check per dependency. `/health/backup` fails when no backup has arrived for 26 hours, and I kept it out of `/health` on purpose: `/health` gates the container healthcheck, and a missed backup shouldn't take the site down.

## A faster, stricter test suite

Tests now run in parallel with ParaTest, one MySQL database per process ([#55](https://github.com/myvars/supplymars/pull/55)). Getting there meant isolating shared state like the cache and backup folders per process; one test class that failed ten times out of ten in parallel now passes every time.

| Where | Before | After |
|---|---|---|
| `make test`, 10-core Mac | 51 s | 27–32 s |
| CI test step, 4 processes | 54 s | 22 s |

Then about 20 PRs reworked the suite itself: written conventions, factories on Foundry 2, one `AccessControlTest` for who can reach what, flow tests that check what a request saved and not only the flash message, the untested routes covered, and domain fixtures built through the aggregates' own methods instead of reflection. Some tests went: round-trip mapping tests and other low-value ones were deleted or shrunk. The suite is about 1,300 test methods in 289 files, all run on every push.

## When things went wrong

Two changes caused production incidents. Both were found quickly, fixed at the cause, and changed how the project works.

**A deploy that waited for a worker that never stopped.** The first deploy with the Scheduler gave the worker 15 minutes to finish its job when asked to stop. The image has no `pcntl`, so the worker never saw the signal. The deploy waited out the full timeout, and live was down for about 20 minutes. The fix is a 30-second grace period: a killed job hasn't advanced the schedule, so it simply runs again ([#84](https://github.com/myvars/supplymars/pull/84)).

**Every page returned 500 after a worker restart.** With OPcache preload on, PHP reused a preloaded class from the compiled container without its interface once a worker had restarted. I reproduced it by forcing a restart every 50 requests, and turned preload off in production ([#123](https://github.com/myvars/supplymars/pull/123)); [the worker mode post](/log/worker-mode) has the trace.

## Also in these weeks

- **The Shadcn UI kit.** Three Flowbite packages and a set of hand-converted components were replaced by the Shadcn kit through Symfony UX Toolkit ([#3](https://github.com/myvars/supplymars/pull/3)). [A UI kit the project owns](/log/a-ui-kit-the-project-owns) tells that story.
- **FormFlow as a package.** My controller pattern lives in its own Composer package, `myvars/form-flow`, now at 1.6 ([#209](https://github.com/myvars/supplymars/pull/209)).
- **A docs review that found bugs.** A six-part review checked every guide and ADR against the code. Checking the docs turned up real bugs in orders, purchase orders, the catalog and sign-in, each fixed in its own PR. The project now has 61 documents, including 17 ADRs.
- **Domain fixes.** Products are repriced when simulated stock and costs change, allocation edge cases are fixed, deleting a record that's still in use is refused, and Doctrine no longer rewrites `public_id` and `updated_at` on every flush.

## How I worked

Most platform changes (FrankenPHP, Mercure, the UI kit, Deptrac) I built first in a smaller skeleton project, then ported here with the differences written down. Every change that touched performance, memory, database size or test speed came with before-and-after numbers from production-like data. Every bug fix came with a test that failed on `main` and passed on the branch. And where Symfony already had the tool, I used it: the Scheduler instead of cron, RateLimiter instead of nginx rules, Lock instead of flag files, Doctrine Messenger instead of a pre-1.0 package.

I did this with Claude Code alongside me, writing much of the code from issues I'd written and plans I'd approved. The decisions, the reviews and the outages were mine.
