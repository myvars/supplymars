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

### Named exceptions

A dependency on another context's behaviour that is deliberate becomes a named exception: a Deptrac layer of its own, which only the contexts that need it may use. Unlike an entry in `skip_violations`, it does not let a second context start using the same class.

- **`Current user`:** `CurrentUserProvider` returns the signed-in user, and `DefaultUserAuthenticator` signs in the default staff user for the simulation commands (ADR 003) in Order, Purchasing and Review. Every context needs one or the other, so any context may use this layer. The skeleton's exception is `CurrentUserProvider` alone; it has no simulation commands. `DefaultUserAuthenticator` is only meant for console commands, and today only they use it.
- **`Order sourcing`:** `OrderAllocator`, and the `CreatePurchaseOrderItem` command with its handler. Sourcing an order from suppliers is Purchasing's job (ADR 002), started from Order: `AllocateOrderHandler` allocates a whole order, and `OrderItemController` lets staff add a purchase order item from the order screen. Only Order may use this layer. Moving the order-screen action into a Purchasing controller would remove the second use, at the cost of a route rename; a domain event would hide a call that has to succeed straight away.
- **`Demo customers`:** `RandomUserFactory` and `RandomAddressFactory`. Order's `DemoOrderCreator` places demo orders as simulated customers. Only Order may use this layer. A domain interface in front of each factory would satisfy the rule and add nothing else.

### Deptrac enforces the rules between contexts

`deptrac.yaml` splits each context into `{Context} Domain` and `{Context} internals` and states which layers each may use. `vendor/bin/deptrac analyse` runs in CI and `make check`. The direction of layers within one context (UI → Application → Domain) is not enforced: the cost of a wrong import there stays inside one context, and the honest list of what Domain uses would make the rule mostly exceptions.

### Existing violations were fixed, not listed

Of the 50 imports that broke the rules, 45 were fixed by a move or a simplification: code that used another context's Doctrine repository class now uses the interface; Order and Purchasing report queries take plain dates and sort fields instead of Reporting's criteria classes; Audit takes its supplier colours and ID lookup from its own code and Shared; the customer page loads its insights from Customer's own repository. The other 5 are the `Order sourcing` and `Demo customers` exceptions above. There is no `skip_violations` list: a new import that breaks the rules is fixed, or recorded as a named exception here.

## Consequences

- A forbidden import fails the build with the class, the file and the layer it may not use.
- A new context must be added to `deptrac.yaml`. Until it is, a catch-all `Unregistered` layer makes every import to or from it fail, so the step cannot be forgotten.
- Repository interfaces grow the query methods other contexts use, mostly Reporting's reads of Order, Customer and Purchasing. They are the visible list of what each context provides to the others. If Reporting's queries keep growing on `OrderRepository`, a query interface of its own in Order's `Domain/` is the next step.
- Extracting a context into its own service still means replacing relations and repository calls with an API. The rule keeps that list short and visible; it does not make extraction free.
