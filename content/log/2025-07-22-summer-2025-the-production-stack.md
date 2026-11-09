---
title: "Summer 2025: shaping the production stack"
period: June – July 2025
summary: With the app in containers, six weeks of building out what runs around it. Redis for sessions and cache, a hardened nginx, a cron container that runs the simulated business, RabbitMQ, local dev tools and a first k6 load test.
refs:
  - label: ADR 003 · Simulation-first design
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/003-simulation-first-design.md
  - label: ADR 013 · Database queue by default
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/013-database-queue-by-default.md
  - label: ADR 015 · Symfony Scheduler in place of cron
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/015-symfony-scheduler-in-place-of-cron.md
  - label: ADR 016 · FrankenPHP in place of nginx and PHP-FPM
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/016-frankenphp-in-place-of-nginx-and-php-fpm.md
---

In early June SupplyMars [moved into containers](/log/leaving-the-bitnami-box), with a pipeline that tests and deploys every push to `main`. The containers ran the app and not much else. Over the next six weeks I added the services around it, one container at a time.

## Redis

Sessions had been files on the server, and the cache was the filesystem too. From 13 June both are in Redis. Sessions use the PHP Redis extension's own session handler, and the app cache uses Symfony's Redis adapter, through SncRedisBundle. Redis is its own container in every environment, so development, CI and production all run the same way.

## nginx for production

The production nginx config gained what a public site needs: HTTP/2, modern TLS only, HSTS and the usual security headers, compression, and a year's caching for the versioned files under `/assets/`, which change name whenever their content changes.

## The crontab that ran the business

SupplyMars is simulation-first: console commands play the customers and the suppliers, so the business runs on its own (ADR 003). On 24 June that got a container of its own, built from a cron stage of the same Dockerfile, and the next day its crontab. That crontab was the business's timetable:

| Every | Job |
|-------|-----|
| 5 minutes | Up to two random orders are placed, by new or existing customers |
| 15 minutes | Suppliers accept up to 20 purchase orders; up to 20 supplier products' stock is updated |
| 30 minutes | Up to 20 orders are built into purchase orders |
| Hour | Up to 100 items ship, and up to 100 are delivered |
| Twice an hour | Today's product and order sales are recalculated |
| Night | Yesterday's sales are calculated in full, and the summaries rebuilt |

Twelve jobs in all, each writing to one log file that was emptied every Sunday. In development the container runs a cron test line only, so a laptop doesn't fill up with simulated orders.

## RabbitMQ

On 4 July Messenger's `async` transport moved from the database to RabbitMQ, with a broker container on each environment. ADR 013, written when I later reversed this, records why it was added: throughput, and room for what the queue might carry later. What it carried then was outgoing email.

## Tools around it

- **A dev-tools compose file** starts the database, Redis, Mailpit and phpMyAdmin for working on the app locally, without the rest of the stack.
- **phpMyAdmin for production** runs on my machine, not the server, and reaches the production database through an SSH tunnel. Every page it shows carries a red "PRODUCTION DATABASE" banner.
- **A first k6 load test** of the homepage and the assets it loads: a baseline to build on, run from the Makefile.

*Update, October 2026: three of these have since been replaced. The queue went back to the database, because it carried only email and the tests needed a running broker (ADR 013). The crontab became a Symfony Scheduler schedule, which runs one job at a time and ended about 11 deadlocks a day between jobs cron had started together (ADR 015). nginx and PHP-FPM gave way to FrankenPHP in [worker mode](/log/worker-mode) (ADR 016). Redis stayed, for the cache and sessions.*
