# Documentation

Index of the SupplyMars docs, with when to read each one. AI agents start at [CLAUDE.md](../CLAUDE.md), which summarises the rules and links here.

SupplyMars is a modular monolith: bounded contexts that use each other only through their `Domain/`, checked by Deptrac in CI ([03-architecture.md](03-architecture.md#rules-between-contexts)).

## Start here

- [01-overview.md](01-overview.md): domain map, which context owns which entities and which doc holds each rule. Read when new to the codebase or looking for where a business rule is written down.
- [02-setup-local.md](02-setup-local.md): running the app locally, a user and data, the [local URLs](02-setup-local.md#development-urls) and ports, RabbitMQ opt-in. Read when setting up a checkout or when a local service will not start.

## Reference

- [03-architecture.md](03-architecture.md): layers, rules between contexts, state between requests, domain events, the queue, where to start reading. Read before adding a class to an unfamiliar context, a listener, a service that holds data or a new context.
- [04-sourcing-and-pricing.md](04-sourcing-and-pricing.md): best source, allocation, status roll-up, the pricing cascade and price calculation. Read before changing `Product`, the allocators, order/PO statuses, the Pricing listeners or `MarkupCalculator`.
- [05-features.md](05-features.md): the business rules of each feature and the files that hold them. Read before changing a feature's behaviour.
- [07-testing.md](07-testing.md): running and writing tests, parallel runs, factories, the checks CI runs. Read before adding a feature, and when a test fails only under `make test` or in CI.
- [08-security.md](08-security.md): users and roles, what only a super admin can do, headers, trusted proxies, rate limits, uploads, CSRF. Read before adding a route, a role check, an upload or a state-changing action.
- [09-cli-reference.md](09-cli-reference.md): what each `app:` command does, when it runs, simulation behaviour, exit codes and locking. Read before running, changing or debugging a command.

## Operations

- [06-operations.md](06-operations.md): scheduled jobs, the Messenger worker, simulated data, MySQL sizing. Read when adding or changing a scheduled job, touching the worker or filling a database.
- [11-runbook.md](11-runbook.md): the production stacks, env files, server set-up, deploy, rollback, backup and restore, troubleshooting. Read before touching the server and when something breaks in production. The host Caddy config it uses is [infrastructure/Caddyfile](infrastructure/Caddyfile).
- [13-monitoring.md](13-monitoring.md): Sentry, `/health`, `/health/backup`, uptime monitoring. Read before changing Sentry, adding a dependency to check or setting up the monitor.
- [infrastructure/playground.md](infrastructure/playground.md): how the public playground differs from live, `PLAYGROUND_MODE`, the nightly reset. Read before changing anything gated on `PLAYGROUND_MODE` or the reset scripts.

## Patterns

- [patterns/FormFlow/README.md](patterns/FormFlow/README.md): which flow fits which action, the app's adapters, how handlers report back. Read before writing or changing a web controller.
- [patterns/FormFlow/Overrides.md](patterns/FormFlow/Overrides.md): recipes for replacing the template a flow renders. Read when an entity needs a page that differs from the shared default.
- [patterns/FormFlow/EmbeddedForms.md](patterns/FormFlow/EmbeddedForms.md): a form in its own Turbo Frame on a host page instead of the modal. Read when a form belongs on a page, such as the reply under a ticket.
- [patterns/InlineEdit/README.md](patterns/InlineEdit/README.md): editing one field in place with `InlineEditFlow`. Read when making a field inline-editable.
- [patterns/API/README.md](patterns/API/README.md): the REST API, its authentication, response format, endpoints and how to add one. Read before adding or changing an endpoint, or calling the API.
- [patterns/Turbo/README.md](patterns/Turbo/README.md): frames, modals and streams. Read when adding a link, a modal form or a frame, or when a flash or modal misbehaves.
- [patterns/Templates/README.md](patterns/Templates/README.md): template naming, comment headers and the variables FormFlow passes. Read before adding a template.
- [patterns/UI/README.md](patterns/UI/README.md): how the server-rendered UI is put together and where each piece goes. Read before adding a component, a controller or a form field.
- [patterns/UI/Forms.md](patterns/UI/Forms.md): the form theme, the field kinds it supports and the app's form helpers. Read when adding a form or a field, or when one renders or submits oddly.
- [patterns/UI/StimulusControllers.md](patterns/UI/StimulusControllers.md): every Stimulus controller, what it does and where it is mounted; side panels. Read before writing a new controller.
- [patterns/UI/Styling.md](patterns/UI/Styling.md): surface, border, type and text-colour classes, and the dark-mode rule. Read when styling anything that is not a kit component.

## Architecture Decision Records

Records of decisions; later amendments are noted in each ADR's Status line.

- [001](adr/001-multi-supplier-sourcing.md): products aggregate several supplier products; the best source is the lowest cost, then the highest stock. Read before changing how a product picks its supplier.
- [002](adr/002-order-line-splitting.md): one order line can be split across suppliers by outstanding quantity, its status derived from its purchase order items. Read before changing allocation or order item status.
- [003](adr/003-simulation-first-design.md): console commands drive the order, purchasing and fulfilment lifecycle with realistic timing. Read before changing a simulation command.
- [004](adr/004-pricing-abstraction.md): three-level markup (product, subcategory, category) with price-model rounding. Read before changing price calculation.
- [005](adr/005-reporting-strategy.md): two-layer reporting, daily records plus pre-computed summaries. Read before adding a report or changing the sales tables.
- [006](adr/006-formflow-controller-pattern.md): FormFlow for thin controllers. Read before writing a controller.
- [007](adr/007-turbo-frame-modal-architecture.md): Turbo Frames with native `<dialog>` modals. Read before changing frames or modals.
- [008](adr/008-server-driven-ui-architecture.md): server-driven UI instead of a JavaScript SPA. Read before adding JavaScript.
- [009](adr/009-support-ticket-system.md): pool-based support tickets with threaded messages. Read before changing tickets or pools.
- [010](adr/010-customer-insights-reporting.md): customer activity, geographic and segment reports on the two-layer model. Read before changing the customer reports.
- [011](adr/011-rest-api-layer.md): a REST API alongside the server-rendered UI, reusing the same handlers. Read before adding an API endpoint.
- [012](adr/012-shadcn-ui-kit.md): Shadcn kit components via Symfony UX Toolkit; Flowbite removed. Read before installing or updating a kit recipe.
- [013](adr/013-database-queue-by-default.md): Messenger on a database queue by default; RabbitMQ as an opt-in. Read before changing the queue or the worker.
- [014](adr/014-architecture-rules.md): framework attributes allowed in the domain; contexts use each other's `Domain/` only; Deptrac in CI. Read before adding a dependency between contexts.
- [015](adr/015-symfony-scheduler-in-place-of-cron.md): scheduled jobs run by Symfony Scheduler in the Messenger worker, one at a time; no cron. Read before adding a scheduled job.
- [016](adr/016-frankenphp-in-place-of-nginx-and-php-fpm.md): one FrankenPHP image in place of nginx and PHP-FPM; rate limits and trusted proxies in the app. Read before changing the web server or the image.
- [017](adr/017-live-updates-with-mercure.md): live order page and private user toasts through FrankenPHP's Mercure hub; one queued refresh per order, deduplicated with locks in Redis. Read before making another page live or notifying a user.

## Records

- [12-technical-review.md](12-technical-review.md): architectural review as of V1.0, 31 March 2026. A dated snapshot, not maintained.

## User manual

- [UserManual/README.md](UserManual/README.md): how staff use the app day to day, screen by screen. Read for what a screen does from the user's side.
