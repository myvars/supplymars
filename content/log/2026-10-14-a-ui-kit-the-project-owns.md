---
title: A UI kit the project owns
period: October 2026
summary: Flowbite and a set of hand-converted components gave way to the Shadcn kit from Symfony UX Toolkit, copied into the project as its own files. Generic names for the kit, descriptive names for the app's own components, and wrappers wherever a composition repeats.
refs:
  - label: ADR 012 · Shadcn UI kit
    url: https://github.com/myvars/supplymars/blob/main/Docs/adr/012-shadcn-ui-kit.md
  - label: "#3 · Replace Flowbite with the Shadcn UI kit"
    url: https://github.com/myvars/supplymars/pull/3
  - label: UI patterns
    url: https://github.com/myvars/supplymars/blob/main/Docs/patterns/UI/README.md
---

From its first commit, SupplyMars built its interface the same way. Find a Flowbite example, convert it to a Twig component, write a Stimulus controller for its behaviour, and keep it working. That gave the app a small library of its own, buttons, cards, dialogs and toasts, on top of three Flowbite dependencies: a bundle for the form theme, the JavaScript package for drawers, which had to be re-initialised on every Turbo render, and a date picker.

It worked, and [the design pass](/log/a-staff-tool-that-feels-alive) in the spring was built on it. But every new interactive component was another hand conversion. And the smaller skeleton project where I try ideas first had already moved to a better way, so templates no longer copied between the two.

## A kit that becomes the project's own code

Symfony UX now has a toolkit for this. `ux:install <recipe> --kit=shadcn` copies a component's Twig templates and Stimulus controller into the project, and that's the end of the dependency: the files belong to the app, to read and change like any other. The Shadcn kit covers about sixty components, around half with ready-made controllers, from dialogs and dropdowns to tabs, tooltips, toasts and a date picker.

I made the change in the skeleton first, then ported it here in one pull request ([#3](https://github.com/myvars/supplymars/pull/3)), one commit per step: the kit and its button, the form theme, the shared components, the toaster, the menus and side panels, the in-house components brought in line, and finally Flowbite removed.

## The kit owns the generic names

The kit installs under fixed names, `Button`, `Card`, `Dialog`, `Toast`, with no option to prefix them. The app's own components already used several of those names. I tried a prefix for the kit first, and dropped it: every install became a manual procedure, and the components used most got the longest names.

So the app's components were renamed for what they are. `Card` became `EntityCard`, a record's card with its edit button, status edge and supplier colours. `Dialog` became `ModalPanel`, the frame for the server-driven modal. `Toast` became `FlashToast`, `Alert` became `Callout`, and so on. Examples from the kit's documentation now paste in unchanged, an upgrade is a re-run and a diff, and the rule for the future is written down: an in-house component never takes a name the kit uses.

## Primitives underneath, wrappers on top

The kit's components are primitives. Where the same arrangement of them repeats across pages, it gets a one-line wrapper built from the kit's own pieces, so the common case reads the same everywhere:

```twig
<twig:StatusBadge status="{{ orderItem.status.value }}" :interactive="true"/>
<twig:EmptyState message="No messages yet"/>
```

`StatusBadge` sits on the kit's `Badge` and looks up the status colour in one place. `EmptyState`, `PageBreadcrumb`, `ResultsPagination` and `DataTable` do the same for theirs. Anything unusual uses the kit's tags directly, and still looks the same, because it is the same markup underneath.

Some things stayed the app's own. The modal is filled from the server through a Turbo Frame, which FormFlow depends on, so the kit's `Dialog` is only for content already on the page. `EntityCard`, `KpiCard`, `InlineEdit` and `SupplierDot` are domain components, not primitives.

## Fitting the kit to the app

The kit had to look like SupplyMars, not the other way round:

- **Tokens on the existing palette.** The kit's role colours, primary, card, border and the rest, are defined as the app's own grey and blue scales, in light and dark, so a kit component and an in-house one sit side by side without a seam.
- **One form theme.** Every form field renders through kit components, from one theme file, including money and percentage fields with their symbol inside the input and a calendar date picker in place of Flowbite's.
- **Buttons the app needed.** The kit's `Button` was tuned to the app's size and weight, and gained the variants the screens use: warning for cancel and rewind, success for approving a review, and solid destructive and secondary styles.
- **Better behaviour for free.** The user menu became the kit's dropdown, with arrow keys, Escape to close and focus returned to the button. Toasts stack, pause while hovered and can be swiped away.

Working in the kit's files turned up a few bugs of its own, such as an input group whose focus ring never showed and a toast whose close button a swipe swallowed. They're fixed in the project's copies, and ADR 012 lists every change to keep when a recipe is updated.

## What it left

Flowbite is gone: the bundle, the JavaScript, the date picker and Popper, and four Stimulus controllers with them. The tests that came with the port check the wrappers, the form theme, the side panels and the user menu on real pages, and the change was checked in a browser in light and dark mode at desktop and phone widths. A new component is now one command away rather than a hand conversion, and the look from the spring carried across.
