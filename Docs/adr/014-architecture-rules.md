# ADR 014: Framework Attributes in the Domain, and the Rule Between Contexts

## Status

Accepted

## Context

`Docs/03-architecture.md` described rules the code did not follow, and nothing checked any of them:

- It said the Domain layer had no dependencies ("pure domain"). Every entity uses Doctrine mapping attributes, Gedmo's `TimestampableEntity` and Symfony Validator constraints, and names its own Doctrine repository in `#[ORM\Entity(repositoryClass: ...)]`. Repository interfaces carry `#[AsAlias]` naming their implementation.
- It said contexts mainly communicate through events and interfaces, not shared entities. About 195 imports reference another context's entities, enums or events, and about 50 use another context's repository interface, for example a Reporting handler loading a `Product` through Catalog's `ProductRepository`.

The skeleton this application shares its patterns with adopted rules for both, enforced by Deptrac (the skeleton's ADR 013). Measured against the same rules, supplymars had 50 imports that broke them: 33 between business contexts and 17 from Shared into business contexts.

## Decision

### The domain may carry framework attributes

Doctrine mapping, Gedmo, validation constraints, Symfony `Uid`, `UserInterface` on `User`, `repositoryClass` and `#[AsAlias]` are allowed in `Domain/`. They describe how the model is stored and checked; the business rules stay in plain methods. Separate XML mapping and a framework-free domain would cost a second description of every entity, for an extraction that is not planned.

### A context may use another context's Domain, never its internals

A context may use another context's `Domain/` directory: entities (including Doctrine relations to them), enums, value objects, events and repository interfaces. It must not use another context's `Application/`, `Infrastructure/` or `UI/`: no handlers, services, Doctrine repository classes, factories, controllers or forms.

Repository interfaces are allowed because the stricter version, entities only, would need a new query class in the owning context for each of the 50 cross-context lookups, nearly all one-line wrappers around the repository they replace. A repository interface is already the owning context's promise of what it provides. Its Doctrine class is the part that can change. When another context needs a query the interface does not have, the method is added to the interface.

### Shared depends on no business context

The security classes that need `User` (`ApiTokenHandler`, `CurrentUserProvider`, `DefaultUserAuthenticator`) moved to `src/Customer/Infrastructure/Security/`. The sidebar badges, which count records in four contexts, moved to Home, which already shows the same counts on the dashboard. `ShippingMethod` moved to the Order domain: the customer order owns it, and it takes a Pricing `VatRate`. The chart colour interface moved from Reporting's UI to `Shared/UI`.

The signed-in user is the one named exception to the rule above. `CurrentUserProvider` returns it, and `DefaultUserAuthenticator` signs in the default staff user for the simulation commands (ADR 003) in Order, Purchasing and Review. Every context needs one or the other, so they share a layer of their own that any context may use. The skeleton's exception is `CurrentUserProvider` alone; it has no simulation commands. `DefaultUserAuthenticator` is only meant for console commands, and today only they use it.

### Deptrac enforces the rules between contexts

`deptrac.yaml` splits each context into `{Context} Domain` and `{Context} internals` and states which layers each may use. `vendor/bin/deptrac analyse` runs in CI and `make check`. The direction of layers within one context (UI → Application → Domain) is not enforced: the cost of a wrong import there stays inside one context, and the honest list of what Domain uses would make the rule mostly exceptions.

### Existing violations are listed, not hidden

Of the 50 imports that broke the rules, most were fixed by a move or a simplification: code that used another context's Doctrine repository class now uses the interface; Order and Purchasing report queries take plain dates and sort fields instead of Reporting's criteria classes; Audit takes its supplier colours and ID lookup from its own code and Shared; the customer page loads its insights from Customer's own repository.

The rest are real dependencies and need a decision each. They are listed under `skip_violations` in `deptrac.yaml`, grouped, with the reason each is still there:

- Order triggering Purchasing's work: allocating an order (`AllocateOrderHandler` → `OrderAllocator`) and creating a purchase order item from the order screen (`OrderItemController` → `CreatePurchaseOrderItemHandler`);
- demo orders built with Customer's random user and address factories (`DemoOrderCreator`).

Deptrac fails on a `skip_violations` entry that no longer matches an import, so the list can only shrink.

## Consequences

- A forbidden import fails the build with the class, the file and the layer it may not use.
- A new context must be added to `deptrac.yaml`. Until it is, a catch-all `Unregistered` layer makes every import to or from it fail, so the step cannot be forgotten.
- Repository interfaces grow the query methods other contexts use, mostly Reporting's reads of Order, Customer and Purchasing. They are the visible list of what each context provides to the others. If Reporting's queries keep growing on `OrderRepository`, a query interface of its own in Order's `Domain/` is the next step.
- Extracting a context into its own service still means replacing relations and repository calls with an API. The rule keeps that list short and visible; it does not make extraction free.
