---
title: Evolving a CRUD abstraction
period: February – June 2024
summary: Four versions of one idea in four months. A helper that every admin screen shared, then a strategy per entity, then a service per operation, then the business operations as named services of their own, the shape the rest of the year was built on.
refs:
  - label: ADR 006 · FormFlow controller pattern
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/006-formflow-controller-pattern.md
---

Turtle's admin is mostly lists and forms: VAT rates, categories, subcategories, manufacturers, products, then suppliers and orders. The first screens, early in February 2024, were written out in full, and each controller repeated the same index, create, show, edit and delete. In the four months that followed, the shared code behind those screens went through four versions.

## Version one: a helper, in a day

On 9 February the screens moved onto a common structure: one set of CRUD templates, a section name, and the same five actions. By the end of the day the repeated code was out of the controllers and in a `CrudHelper`: the product screens moved first, and the other four sections after them. A controller set its section name in its constructor and handed each action to the helper:

```php
#[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
public function new(Request $request): Response
{
    return $this->crudHelper->renderCreate($request, new Category(), CategoryType::class);
}
```

The helper paged the index with Pagerfanta, bound the form to the entity, persisted it, set a flash message, answered a Turbo Frame with a stream that refreshed the page, and worked out the route names from the section, such as `app_category_index`. The five catalogue controllers shrank by about 40%. The last commits of the day added error handling: a missing record showed an empty page or redirected with a warning, and each save was wrapped in a catch that turned any failure into an error flash.

The helper saved every entity the same way, persist and flush, and an entity that needed more had no place to say so.

## Version two: a strategy per entity

On 23 April the save step became a strategy. A `CrudStrategyInterface` had create, update and delete, a default `CrudStrategy` persisted and flushed, and an entity that needed more could have its own. Category and product got one each, injected by the controller and set on the helper, though both still only saved: the place for custom logic came before anything used it. Paging moved into a `Paginator` service.

## Version three: a service per operation

A week later, on 30 April, the commit that brought in customer and purchase orders split the helper by operation: `CrudCreator`, `CrudReader`, `CrudUpdater`, `CrudDeleter` and `CrudIndexer`, each working from an options object that a controller could fill in itself. The strategies went per operation too. Creating an order isn't the same job as creating a category: its form fills a DTO, not the entity, and a strategy turns the DTO into an order. The order screens built their own form and said where to go on success; the catalogue screens still passed a form type, and the services filled in the options for them.

## Version four: actions named for what they do

Over late May and June the order screens pushed it one step further. On 26 May the order logic moved out of the strategies into services in `Service/Order` and `Service/PurchaseOrder`, each with a method any code could call, and the strategies shrank to thin wrappers named for what they do, such as `CreateOrderStrategy` and `EditPOItemStrategy`. On 12 June the wrappers went. The CRUD layer now takes any `CrudActionInterface`, and the services, renamed as verbs, implement it.

Five of them, `CreateOrder`, `CreateOrderItem`, `EditOrderItem`, `EditPurchaseOrderItem` and `ChangePurchaseOrderItemStatus`, have a `handle()` for the CRUD layer and a `fromDto()` for everything else; `CreatePurchaseOrder` and `CreatePurchaseOrderItem` are called from code directly. That second door mattered three months later: the [simulator](/log/six-days-to-a-supply-chain-that-runs-itself) places orders through `CreateOrder` and moves purchase order items through `ChangePurchaseOrderItemStatus`, the same services the screens use.

## The trade each one made

Each version moved a decision closer to the code that owned it. The first put everything in one place, which removed the repetition but left no room for an entity that was different. The second made room per entity; the third per operation; the fourth gave the operations names and let them stand without the CRUD layer at all. What stayed the same throughout was the catch-all around each save, turning any exception into a generic error flash.

*Update, November 2026: the options classes became one `CrudOptions` in [the winter of 2025](/log/winter-2025-295-tests-to-1014). The [rebuild by bounded context](/log/rebuilding-by-bounded-context) replaced the CRUD layer with FormFlow, whose handlers return a `Result` instead of swallowing exceptions, and turned the verb-named services into commands and handlers.*
