# Technical Review: SupplyMars

*An architectural and engineering review of the SupplyMars project, produced on 31 March 2026 to coincide with the V1.0 public release.*

This review is an honest assessment of where the project stands today: what it does well, where it shows its age, and what could be improved. Work will continue on new features and refinements, and the recommendations outlined here will be addressed over time as the project evolves.

---

## 1. What the Project Is

SupplyMars is a full-stack supply chain management platform built with PHP 8.5 and Symfony 8.0. It models a Mars-themed e-commerce and operations business: products sourced from multiple suppliers, dynamic pricing with hierarchical markup cascades, customer orders that split across suppliers based on stock and cost, purchase order lifecycle management, reporting dashboards, a support ticket system, product reviews, and a REST API.

It is a modular monolith, organised into eleven bounded contexts following domain-driven design principles. The frontend uses server-rendered Twig templates enhanced with Hotwire Turbo, Stimulus controllers, and Tailwind CSS. Infrastructure is Dockerised, deployed to AWS via GitHub Actions, and includes a separate "playground" environment for safe public demos.

This is not a toy project or a tutorial exercise. It is a realistic, end-to-end system with genuine domain complexity, covering everything from database migrations to transactional emails, from ULID-based public identifiers to two-layer reporting aggregation. The codebase runs to roughly 700 source files, 300 test files, and 57 documentation files.

---

## 2. Why It Exists

SupplyMars serves as a working portfolio piece and engineering demonstration. The domain, multi-supplier sourcing with dynamic pricing and order fulfilment, was chosen because it is inherently non-trivial: it involves state machines, monetary precision, event-driven cascades, and realistic operational workflows that resist simplification. This is a deliberate choice. A simpler domain would have been easier to build but would demonstrate less.

The project reflects someone who wanted to show not just that they can write clean code, but that they can design a coherent system: make architectural decisions, document them, enforce consistency across a growing codebase, handle cross-cutting concerns, build infrastructure, write tests, and ship the result to production with a CI/CD pipeline.

It also reflects a bias toward building real things rather than talking about them. The playground environment, the simulation system that generates realistic data, the operational runbook, these are the choices of someone who has shipped software in production and understands what it takes to keep it running.

---

## 3. What It Is Trying to Achieve

The project appears to pursue several goals simultaneously:

**Technical goals.** Demonstrate fluency with modern PHP and Symfony: strict typing, readonly properties, PHP 8.5 enums, attribute-based mapping, the latest Doctrine ORM, and Symfony 8.0 features. Show that PHP can be used to build well-architected, maintainable systems, not just quick scripts.

**Architectural goals.** Apply domain-driven design in a way that is practical rather than dogmatic. Bounded contexts with clear layering, but adapted to the actual complexity of each context rather than applied uniformly for its own sake. The Home context is a single controller; the Purchasing context has 131 files across four layers. This is the right kind of proportionality.

**Quality goals.** Maintain a high standard across the board: PHPStan at level 7, PHP-CS-Fixer with Symfony rules, Rector for automated modernisation, comprehensive testing with Foundry factories and browser-based flow tests, and thorough documentation.

**Showcase goals.** Demonstrate breadth of experience: backend architecture, frontend interactivity, infrastructure, CI/CD, security, operational tooling, API design, and technical writing. The project is designed to show range.

---

## 4. What Is Covered

The scope of this project is unusually broad for a solo effort. It covers:

**Domain modelling.** Rich entities with encapsulated business logic, value objects for identifiers and monetary values, domain events (both synchronous and asynchronous via RabbitMQ), aggregate roots with invariant enforcement, and a clean separation between domain interfaces and infrastructure implementations. Monetary calculations use `bcmath` throughout, which is the correct choice.

**Architecture and code organisation.** Eleven bounded contexts, each with a consistent four-layer structure (Domain, Application, Infrastructure, UI). A shared kernel provides cross-cutting concerns: event infrastructure, the FormFlow abstraction, API base classes, Twig components, and identity resolution. The architecture is documented in six ADRs that explain not just what was decided, but why alternatives were rejected.

**Admin and back-office workflows.** Product management, supplier management, order creation and fulfilment, purchase order lifecycle, stock management, pricing configuration with cascading recalculation, customer management, support tickets, product review moderation. These are not stub pages; they are working workflows with forms, validation, state transitions, and audit logging.

**UI and UX consistency.** A component library of 22 Twig components (buttons, cards, badges, modals, inline editors, search layouts, charts), 23 Stimulus controllers, dark mode support, Turbo-powered navigation without full page reloads, view transitions with direction-aware animations, and a singleton modal pattern. The frontend is cohesive and feels like a single system, not a collection of pages.

**Testing.** 301 test files with a clear testing pyramid: domain unit tests for business logic, integration tests for handlers, and browser-based flow tests for UI. 34 Foundry factories for test data. DAMA Doctrine Test Bundle for transaction rollback per test. The testing approach is practical and focused on the areas that matter most.

**CI/CD and deployment.** A GitHub Actions pipeline that runs code style checks, PHPStan, Doctrine schema validation, and the full test suite on every push. Deployment to AWS Lightsail via SSH with smoke tests. Two production stacks (live and playground) managed via Docker Compose with multi-stage builds.

**Infrastructure.** A seven-stage Dockerfile covering development, production, cron, and playground reset. Five Docker Compose configurations for different environments. Redis for caching and sessions, RabbitMQ for async messaging, MySQL 8.4, Nginx with Caddy reverse proxy, S3 for file storage, Amazon SES for email.

**Developer experience.** A Makefile with clear targets, a local dev setup using Symfony CLI, Mailpit for email testing, PhpMyAdmin, Xdebug support, and a thorough setup guide with troubleshooting.

**Reporting and analytics.** A two-layer aggregation system: daily granular records (Layer 1) rolled up into time-scoped summaries (Layer 2) across multiple dimensions (orders, products, customers, geography, segments). Chart.js dashboards with interactive drill-down.

**Documentation.** 57 files covering architecture, setup, features, security, operations, CLI reference, testing strategy, a user manual with 15 feature guides, and a production runbook. The documentation is structured for multiple audiences: developers, operators, and stakeholders.

**Simulation system.** Console commands that drive the full order/purchasing/fulfilment lifecycle with realistic timing, randomisation, and error handling. This is not just seed data; it is a continuous simulation that keeps the system populated with fresh, realistic state for demos and reporting.

**REST API.** Seven API controllers with OpenAPI documentation attributes, stateless Bearer token authentication, RFC 7807 error responses, and resource serialisation. The API reuses domain handlers rather than duplicating logic.

---

## 5. What Is Good

**The architecture is coherent and well-reasoned.** The bounded context structure is not decorative. Each context genuinely encapsulates a distinct area of the domain, with cross-context communication happening through domain events rather than direct service calls. The layering within each context is consistent and serves a clear purpose. The shared kernel is well-scoped: large enough to prevent duplication, small enough to avoid becoming a dumping ground.

**The FormFlow abstraction is a genuine contribution.** Rather than repeating the same form-handling boilerplate across dozens of controllers, the project extracts a reusable flow system with four variants (FormFlow, CommandFlow, DeleteFlow, SearchFlow) plus an inline editing extension. Controllers are reduced to 5-10 lines per action. This is the kind of abstraction that emerges from building enough of something to see the pattern, and it is well-executed: fluent context builders, Turbo-aware redirects, auto-update detection, and proper error handling.

**The domain models are rich and well-encapsulated.** Entities have private constructors with factory methods, status transitions are validated at the model level, invariants are enforced with exceptions, and derived state is recalculated through explicit methods. This is not an anemic domain model; the business logic lives where it belongs.

**Type safety is taken seriously throughout.** Strong-typed ID value objects prevent accidental mixing of identifiers across contexts. PHP enums encapsulate status logic with transition rules, UI properties, and chart colours. Monetary values use `bcmath` with `numeric-string` type hints. PHPStan at level 7 enforces this discipline.

**The pricing cascade is genuinely complex and well-handled.** Three-level markup inheritance (product, subcategory, category) with event-driven recalculation, six price models, and smart conditional updates that only recalculate affected products. The worked examples in the documentation make the logic transparent.

**The testing approach is practical.** Domain tests are pure unit tests. Handler tests bootstrap the kernel and use factories. Flow tests use a real browser. The testing pyramid reflects real-world priorities rather than chasing coverage metrics. The use of Foundry factories with builder methods (`.asStaff()`, `.asSuperAdmin()`, `.withStandardRate()`) makes test setup readable and maintainable.

**The documentation is exceptional.** This is not an afterthought. The ADRs are well-structured, the pattern documentation is detailed enough to be useful, the user manual is written for a non-technical audience, and the runbook covers actual operational procedures. The documentation rivals what you would find in a well-run engineering team.

**The simulation system shows operational thinking.** Building a system that can generate its own realistic data, run through its own lifecycle, and keep itself in a demonstrable state is a sign of someone who thinks about how software lives in production, not just how it looks in a code review.

**The frontend is modern without being complicated.** The choice of Hotwire Turbo over a JavaScript SPA framework is well-suited to the application type. The Stimulus controllers are small and focused. The component library provides consistency without requiring a design system team. Dark mode, view transitions, and inline editing are genuine polish, not gimmicks.

**The CI/CD pipeline is complete.** Code style, static analysis, schema validation, and tests all run on every push. Deployment is automated with smoke tests. There is a separate playground environment with nightly resets. This is production-grade infrastructure.

---

## 6. What Is Weaker, Older, or Could Be Improved

No project of this scope is uniformly perfect, and it would be dishonest to pretend otherwise. The following observations are offered in the spirit of constructive review, with an understanding that many of these reflect the natural evolution of a project built over time.

### Dual ID System: A Visible Transition

The most visible sign of the project's evolution is the dual identifier system. Entities carry both an auto-increment `id` (used internally by Doctrine) and a ULID `publicId` (used in URLs and APIs). The `HasPublicUlid` trait marks the `publicId` column as nullable, and there is a commented-out lifecycle callback for backfilling, suggesting this was retrofitted onto an existing schema rather than designed in from the start.

This is not a flaw in itself. It is a pragmatic migration strategy, and the direction is clearly correct: ULIDs for external interfaces, auto-increment for internal relationships. But the nullable column, the backfill command in the shared kernel, and the dual-access patterns create a small amount of ongoing cognitive overhead. In a greenfield project, you would likely use ULIDs as the sole identifier from day one.

### API Coverage Is Partial

The REST API covers the Catalog and Order contexts but does not extend to Purchasing, Pricing, Reporting, or the support ticket system. For a project that documents API design as a first-class pattern (ADR-011), this feels incomplete. The existing API controllers are well-built, with OpenAPI attributes, proper authentication, and resource serialisation, but the coverage creates an asymmetry between what the web UI can do and what the API exposes.

This may be a deliberate scope decision, and the foundation is clearly there to extend it. But for a portfolio piece, a more complete API surface would strengthen the demonstration of API design skills.

### Customer-Facing Storefront Is Minimal

The public-facing side of the application is limited to a landing page, legal pages, and a public catalog API endpoint. The vast majority of the system is an admin/back-office application. There is no customer-facing order flow, no shopping cart, no checkout process. This is fine for the project's stated goals, as it is primarily a supply chain management system, but it does mean that one dimension of full-stack development (customer-facing UX, conversion flows, payment integration) is not demonstrated here.

### Some Contexts Are Simpler Than Others

The DDD layering is applied consistently, but some contexts (Audit, Home, Pricing) are relatively thin. The Audit context is essentially an append-only log. The Home context is a single controller. This is not wrong; proportional complexity is actually good architectural judgment. But it does mean that the project's strongest demonstrations of DDD depth are concentrated in a few contexts (Order, Purchasing, Catalog), while others are more structural.

### Reporting Layer Has Some Complexity Cost

The two-layer reporting aggregation is architecturally sound and well-documented. However, the number of summary entities, handlers, and console commands it requires is substantial. Five summary types, each with a calculation command and a summary command, adds up to significant surface area. For a project at this scale, the reporting layer is arguably the most complex subsystem relative to its user-facing output (a handful of dashboard charts). A lighter approach, perhaps using database views or materialised queries, might achieve similar results with less code.

This is a trade-off, not a mistake. The two-layer approach scales better and is more testable. But it is worth noting that the reporting infrastructure is a significant investment.

### Console Command Coverage Could Be More Uniform

The simulation system is impressive, but the console commands vary in sophistication. Order creation and purchase order lifecycle commands are well-developed with dry-run support, progress tracking, and configurable parameters. Some reporting commands are simpler. The error-handling pattern (continue-on-failure) is documented but applied somewhat inconsistently across commands.

### Test Coverage Is Practical but Not Comprehensive

The testing approach is sound, but coverage is not uniform across all contexts. Handler tests and flow tests are well-represented for the core contexts (Order, Catalog, Purchasing), but some edge cases in the pricing cascade, reporting aggregation, and support ticket system may have lighter coverage. The project would benefit from more targeted integration tests for the event-driven recalculation paths, where subtle bugs are most likely to hide.

### Minor Inconsistencies

A few small inconsistencies are visible across the codebase, typical of a project that has evolved over time:

- The `Note` context uses a different naming convention from the documentation, which refers to it as a "support ticket system." The bounded context name (`Note`) does not immediately communicate its purpose compared to, say, `Support`.
- Some controllers (notably `TicketController`) mix FormFlow usage with manual form handling within the same class. The manual handling is justified (display-only forms passed to templates), but it creates a slight inconsistency in how forms are managed.
- The `ResetPasswordController` does not use FormFlow, which is reasonable given the third-party bundle integration, but it means authentication flows follow a different pattern from the rest of the application.

These are minor points. They reflect the reality of building a system over time rather than any lack of discipline.

---

## 7. Recommendations

### High Value

**Extend the API to match web UI coverage.** The API pattern is well-established and high-quality. Extending it to cover Purchasing, Pricing, and Reporting would strengthen the project as a portfolio piece and demonstrate API design at broader scale. The foundation (AbstractApiController, ApiResponse, resource classes) makes this relatively low-effort per endpoint.

**Complete the ULID migration.** Make `publicId` non-nullable across all entities and remove the backfill infrastructure. This would clean up the dual-ID cognitive overhead and signal that the migration is complete. If the auto-increment IDs are still needed for Doctrine relationships, that is fine, but the nullable column and backfill command are legacy artifacts that could be retired.

### Consistency

**Rename the Note context to Support.** The context models a support ticket system with pools, tickets, and messages. `Support` would communicate this more clearly to someone reading the directory structure for the first time, and would align with the documentation.

**Unify form handling in authentication flows.** Consider whether the ResetPasswordController and RegistrationController could be brought closer to the FormFlow pattern, even if they require custom handling for third-party bundle integration. A thin adapter or custom flow type might preserve consistency without fighting the bundle.

### Testing

**Add targeted integration tests for event-driven cascades.** The pricing recalculation, order status propagation, and reporting aggregation are the areas most likely to harbour subtle bugs. End-to-end tests that verify the full cascade (change a category markup, confirm all affected product prices are updated correctly) would add confidence in the most complex parts of the system.

**Consider contract tests for the API.** As the API surface grows, lightweight contract tests (validating response shapes and status codes) would help ensure that changes to handlers or resources do not accidentally break the API contract.

### Infrastructure

**Add structured logging and monitoring hooks.** The runbook mentions monitoring recommendations, but the codebase does not include structured logging or integration with an observability platform. Even basic structured logging (JSON logs with context) would make the operational story more complete and demonstrate production readiness.

**Consider health check endpoints.** Docker healthchecks exist for infrastructure services, but the application itself does not expose a `/health` endpoint that verifies database connectivity, Redis availability, and queue status. This is a small addition that rounds out the operational story.

### Documentation

**Add a high-level system diagram.** The documentation is thorough in text form, but a visual architecture diagram (showing bounded contexts, infrastructure services, and data flow) would help new readers orient themselves quickly. Even a simple ASCII or Mermaid diagram in the architecture document would add value.

---

## 8. Overall Assessment

SupplyMars is a serious, well-engineered project that demonstrates genuine depth across the full stack. It is not a tutorial project dressed up with good documentation; it is a working system with real domain complexity, thoughtful architecture, and production-grade infrastructure.

The strongest aspects of the project are its architectural coherence, the quality of its domain modelling, the FormFlow abstraction, and the exceptional documentation. These are not things you learn from a framework tutorial. They reflect experience with building and maintaining real systems, making trade-offs, and evolving a codebase over time without losing consistency.

The weaker areas, partial API coverage, the visible ULID migration, lighter testing in some contexts, are the kind of imperfections that exist in every real codebase. They reflect the natural evolution of a project built incrementally rather than designed all at once. Crucially, the direction of travel is clear and correct in every case: the project is moving toward better patterns, not away from them.

What makes this project credible is not that it is perfect, but that it is honest. The ADRs document trade-offs, not just decisions. The documentation acknowledges what is and is not protected by the security model. The simulation system exists because someone understood that a demo needs realistic data, not lorem ipsum. The playground environment exists because someone thought about how to let others explore the system safely.

For an external reader, whether an employer, collaborator, or technical reviewer, this project says several things clearly: the engineer behind it understands software architecture and can apply it proportionally. They can build a complete system, not just isolated features. They think about operations, testing, documentation, and developer experience, not just code. They have continued to evolve their approach over time, adopting modern patterns (ULIDs, Turbo, Stimulus, PHP 8.5 features) while maintaining a coherent whole.

It is a substantial and credible body of work.
