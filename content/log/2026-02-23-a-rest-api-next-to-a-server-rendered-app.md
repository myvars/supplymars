---
title: A REST API next to a server-rendered app
period: February 2026
summary: A JSON API for the catalog and orders that reuses the web's handlers, documents itself, and leaves the server-rendered UI untouched. And why not API Platform.
figures:
  - value: "16"
    label: endpoints
  - value: "8"
    label: public catalog endpoints
  - value: "0"
    label: domain logic duplicated
refs:
  - label: ADR 011 · REST API layer
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/011-rest-api-layer.md
  - label: API pattern docs
    url: https://github.com/myvars/supplymars/blob/main/Docs/patterns/API/README.md
  - label: API documentation (live)
    url: https://www.supplymars.com/api/doc
---

SupplyMars is server-rendered on purpose: controllers return HTML, Turbo makes it feel fast, and Symfony's forms and validation do the heavy lifting. That is the right shape for a form-heavy back office. It is the wrong shape for another system that wants the catalog or needs to place an order. An integration shouldn't have to parse HTML.

So in February I added a JSON API alongside the web UI, with one rule above all others: **the API must not contain any business logic of its own.**

## One domain, two front doors

Every API controller calls the same command handlers and repository interfaces as its web counterpart. Creating an order through `POST /api/v1/orders` runs exactly the handler the web form runs, with the same validation, the same domain events and the same audit log. If a rule changes, it changes once.

What differs is only the presentation:

- **Output** is shaped by a readonly `Resource` class for each type, with a `fromEntity()` factory and a `toArray()` method. A list and a detail view can show different fields without the entity knowing anything about JSON.
- **Input** arrives through payload DTOs mapped with Symfony's `#[MapRequestPayload]` and checked with Validator constraints.
- **Errors** follow RFC 7807: every failure is a `problem+json` response, and validation failures carry a `violations` list a client can act on.
- **Responses** share one envelope: `data`, plus `meta` and `links` on collections, so pagination works the same everywhere.

## Kept apart from the web

The API has its own firewall, stateless and using bearer tokens, placed before the web firewall so an API request never touches a session or a CSRF token. The catalog endpoints are public and read-only; the order endpoints need a staff token. That split lives in `access_control`, in one place, not in each controller.

Sixteen endpoints shipped: list and show for products, categories, subcategories and manufacturers, and for orders list, show, create, cancel, allocate, and add, update and remove items. Each is documented with OpenAPI attributes on the controller and the resource, and NelmioApiDoc serves a Swagger UI at `/api/doc`, generated from attributes that sit next to the code they describe.

## Why not API Platform

API Platform is the obvious choice for an API in Symfony, and the first option the ADR weighs. It works best when entities are the API resources. Mine are rich aggregates with typed ULID public IDs, domain events and behaviour behind methods, and the API needed to go through the same command handlers as the web. Bending those aggregates to fit its resource model would have meant changing the domain to suit the delivery mechanism. GraphQL was more flexibility than any consumer needed. Custom controllers on top of the existing handlers cost a little more per endpoint and kept the domain exactly as it was.

ADR 011 records the decision and the alternatives.

One part was left unfinished: tokens were stored in plain text, and nothing issued them yet, so the order endpoints had no real consumer.

*Update, October 2026: both are fixed. Tokens are stored only as SHA-256 hashes, issued by a console command and shown once ([#30](https://github.com/myvars/supplymars/pull/30)).*
