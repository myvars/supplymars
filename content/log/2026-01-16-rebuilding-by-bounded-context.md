---
title: Rebuilding by bounded context
period: October 2025 – January 2026
summary: Three months on a branch, turning folders by type into eight bounded contexts, with commands, handlers, repository interfaces and a new controller pattern, FormFlow. 1,200 files changed, and no change to the schema.
refs:
  - label: Architecture
    url: https://github.com/myvars/supplymars/blob/main/Docs/03-architecture.md
  - label: ADR 006 · FormFlow controller pattern
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/006-formflow-controller-pattern.md
  - label: ADR 014 · Architecture rules
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/014-architecture-rules.md
---

By October 2025 SupplyMars was organised the way most Symfony apps start: by kind of class. `src/` had `Controller/`, `Entity/`, `Form/`, `Repository/`, `Service/`, `EventListener/`, `DTO/` and ten more folders, 337 files in all. Products, orders, suppliers and pricing were spread across all of them. A purchase order's rules sat next to a VAT rate's because both were entities.

The design had been heading towards the business since 2024, with rich entities and domain events, and folders by type had become the thing in its way. That October I rebuilt the code around the business instead. It merged on 16 January 2026.

## Groundwork on main

The first week stayed on `main`, with changes small enough to ship on their own:

- **A public ULID on every entity**, so URLs could stop using database IDs: [a post of its own](/log/a-public-ulid-for-every-entity).
- **Domain events as small facts**, dispatched once after each flush instead of by hand: [also its own post](/log/domain-events-take-two).
- **PHP 8.4 and dependency updates.**

Then I branched, and the branch's first commit moved the tests to Foundry 2.7. Nothing else merged to `main` until the branch did.

## Eight contexts and a shared kernel

The new `src/` has one directory per bounded context: **Audit, Catalog, Customer, Home, Order, Pricing, Purchasing and Reporting**, plus **Shared** for what every context needs, such as the markup calculator, the price models and the base class for public IDs. Each context has up to four layers:

- **Domain**: the entities, value objects, enums, events, and the repository interfaces.
- **Application**: commands, their handlers, search criteria and event listeners.
- **Infrastructure**: the Doctrine repositories and anything else that talks to the outside.
- **UI**: controllers, forms, console commands and Twig.

The aim between contexts was that one may use another's domain, but never its internals. When Pricing reprices a category's products, it loads the category through Catalog's `CategoryRepository` interface, never the Doctrine class behind it. Each context registers its own Doctrine mapping, and every table kept its name, so 1,200 changed files came with no migration at all.

## Every write is a command

Before, forms were bound straight to entities: `new Manufacturer()` went into a `ManufacturerType`, and a generic CRUD layer, rewritten several times since February 2024, persisted whatever came back. Now every write follows one path. A form fills a form model, a mapper turns that into a readonly command, and the command's handler loads the entity, calls a domain method and flushes:

```php
final readonly class UpdateManufacturerHandler
{
    public function __invoke(UpdateManufacturer $command): Result
    {
        $manufacturer = $this->manufacturers->getByPublicId($command->id);
        if (!$manufacturer instanceof Manufacturer) {
            return Result::fail('Manufacturer not found.');
        }

        $manufacturer->update(name: $command->name, isActive: $command->isActive);
        // validate, then flush

        return Result::ok('Manufacturer updated');
    }
}
```

The handler returns a `Result`, ok or failed with a message, rather than throwing for an expected refusal. At the merge there were 48 commands and 52 handlers, and 24 repository interfaces, each implemented in its context's infrastructure and wired to it with `#[AsAlias]`.

## FormFlow

With every screen built the same way, the controllers could share the code that runs them. FormFlow is that code. A controller action names the form, the mapper, the handler and a context, and the flow does the rest: it builds and handles the form, maps it, calls the handler, adds the flash message, and redirects in a way Turbo understands, or re-renders with a 422 on failure.

```php
return $flow->form(
    request: $request,
    formType: ManufacturerType::class,
    data: ManufacturerForm::fromEntity($manufacturer),
    mapper: $mapper,
    handler: $handler,
    context: FlowContext::forUpdate(self::MODEL)->allowDelete(true),
);
```

It started with five flows: `FormFlow` for create and update, `SearchFlow` for index pages, `ShowFlow`, `DeleteFlow`, and `CommandFlow` for actions without a form. The old CRUD layer had been trying to give every screen one shape; FormFlow gives them one shape and keeps the entities out of the forms. It kept growing after the merge, gaining inline editing and embedded forms in February, and in June it [became a package of its own](/log/formflow-becomes-a-package).

The URLs changed with it. Routes are named by context (`app_catalog_manufacturer_edit`), and 64 controller arguments load their entity by its ULID through one value resolver.

## The tests

The work ran from mid-October to mid-January, with a clean-up the day it merged.

The old test suite was written against the old classes, so I moved it aside into `tests_old/`, which PHPUnit doesn't run, and wrote a new suite, context by context. On the day the branch merged, 415 test methods ran, against 1,004 before it. Order and Reporting had no tests yet; [the January roundup](/log/php-8-5-phpstan-7-and-writing-it-down) covers how the suite was rebuilt.

*Update, October 2026: Deptrac now checks the rule between contexts and fails the build on a new breach; [Five weeks, 119 pull requests](/log/five-weeks-119-pull-requests) tells how the imports that broke it were fixed, and ADR 014 records the rule.*
