---
title: FormFlow becomes a package
period: 2 – 4 June 2026
summary: My controller pattern moves out of the app into its own versioned Composer package, with ports the app implements, names that say what each flow is for, and a CSRF flaw fixed on the way.
refs:
  - label: ADR 006 · FormFlow
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/006-formflow-controller-pattern.md
  - label: myvars/form-flow
    url: https://github.com/myvars/form-flow
  - label: FormFlow pattern docs
    url: https://github.com/myvars/supplymars/blob/main/Docs/patterns/FormFlow/README.md
---

Every controller action in SupplyMars does one of a handful of things: show a form and save it, change a state, confirm and then act, list and search, or edit one field in place. FormFlow is the pattern that handles each of those once, so a controller only says *what* happens:

```php
return $flow->form(
    request: $request,
    formType: CategoryType::class,
    data: new CategoryForm(),
    mapper: $mapper,
    handler: $handler,
    context: FlowContext::forCreate($this->model()),
);
```

The form binds to a form model, a mapper turns it into a command, a handler does the work and returns a result, and the flow takes care of validation, the 422 on failure, the flash message, the redirect and Turbo. It grew out of two years of iterating on how this app's controllers should work, and by this spring it was stable.

It was also living in two places. I use it in a smaller skeleton project as well, where I try ideas before bringing them here. Two copies of the same code drift, so in June I made it a package: `myvars/form-flow`, versioned and published on Packagist, and both projects depend on it.

## Getting the API right first

Extracting code is the moment its API becomes a promise, so I cleaned it up before publishing rather than after.

- **Fewer ways to say the same thing.** `FlowContext` had grown overlapping factory methods. Seventeen `forSuccess()` calls folded into one factory, which became `forAction()` with the rename below, and the unused ones went.
- **Names that say what a flow is for.** `CommandFlow` became `ActionFlow`: it runs a state change, like approving a review or locking an order. `DeleteFlow` became `ConfirmFlow`, because deleting is only one of the things that needs a confirmation step.

## A flaw found on the way

The rename made me look at every action that used the confirm step, and that turned up a real problem. Cancelling an order, rewinding a purchase order and removing a supplier product all went through the delete flow, so their CSRF tokens were all built from the key `delete` plus the record's ID. On a supplier product, "delete" and "remove" were literally sharing one token: a token issued for one action was valid for the other.

Each confirmable action now has its own key, `cancel`, `rewind` or `remove`, and the confirmation pages that had been rendered by hand go through the flow like everything else.

## Ports the app implements

The package must not know anything about SupplyMars, and the app shouldn't change how it works to suit the package. So the package defines small interfaces, and the app's existing classes implement them:

- The app's `Result`, `RedirectTarget` and flash messenger implement the package's result, redirect and flasher interfaces.
- The search flow takes a Pagerfanta adapter instead of a Doctrine query, so the package has no opinion about persistence. Thirteen index controllers now pass `$repository->findByCriteria($criteria)`.
- The inline edit flow no longer saves anything itself. The callback that applies the change also owns the flush, so the app decides when and how data is persisted.
- The package ships plain default templates, and the app overrides them with its own, so the look stays in the app.

Then the switch itself: the in-app `FormFlow` directory and its tests went, eighteen files changed their imports to the package's namespace, and the bundle was registered. About 3,000 lines left the app, and all 1,050 tests passed.

## Why it was worth it

The pattern now has one home, a version number and its own tests. The app depends on it the way it depends on any other library, through a constraint in `composer.json`, and the skeleton and SupplyMars can now differ only by a version number. ADR 006 records the move, and the project's instructions say plainly that FormFlow lives in the package and must not come back into `src/`.
