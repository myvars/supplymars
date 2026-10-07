# ADR 012: Shadcn UI Kit via Symfony UX Toolkit

## Status

Accepted

## Context

The UI is server-driven (ADR 008): Twig Components for markup, Stimulus for behaviour, Tailwind for styling. Until now, every new component was built by hand — find a Flowbite example, convert it to a Twig Component, write a Stimulus controller, then keep it working. The result was a small in-house library (Button, Card, Dialog, Toast, …) plus three separate Flowbite dependencies:

- `tales-from-a-dev/flowbite-bundle` for the Symfony form theme
- the `flowbite` JavaScript package for drawers, re-initialised on every Turbo render
- `flowbite-datepicker`

Symfony UX now ships `symfony/ux-toolkit`: a dev-only command, `ux:install <recipe> --kit=<kit>`, that copies Twig component templates and Stimulus controllers into the project. There is no runtime dependency; the copied files belong to the project. Its Shadcn kit covers around 60 components, roughly half with ready-made controllers (dialog, dropdown, combobox, tabs, tooltip, toast, date picker).

The change was first made in the project skeleton this application shares its component set with (its ADR 011), where a spike compared the kit against the in-house components. It was then ported here; "Differences in this application" below lists where the two diverge.

## Decision

### Use the Shadcn kit as the source of UI primitives

New general-purpose components come from the kit rather than being hand-built. Kit components live in `templates/components/` under the kit's own names and are used as `<twig:Button>`, `<twig:Field>`, `<twig:Dialog>`, and so on. Their controllers live in `assets/controllers/` alongside the app's own.

### The kit owns the generic names

The kit installs to `templates/components/` with fixed names and has no prefix option. Rather than prefix the kit on every install, the in-house components that collided were renamed to say what they are:

| Was | Now | What it is |
|-----|-----|------------|
| `Card` | `EntityCard` | Card for a record: edit/show links, status highlight, colour schemes |
| `CardFooter` | `EntityCardFooter` | Its "updated X ago" footer |
| `Dialog` | `ModalPanel` | Content frame for the Turbo modal: title bar, close button, body |
| `Toast` | `FlashToast` | A flash message shown as a toast |
| `Alert` | `Callout` | One-line coloured message with a matching icon |
| `Breadcrumb` | `PageBreadcrumb` | Two-level breadcrumb: parent link, current page |
| `Pagination` | `ResultsPagination` | "Showing X–Y of Z" plus page links for a Pagerfanta pager |

This keeps `ux:install` working as designed, lets examples from the kit's documentation paste in unchanged, and makes an upgrade a re-run plus a diff review.

The spike first installed the kit under a `Ui:` prefix (install to a scratch directory, rewrite `<twig:X>` to `<twig:Ui:X>`, copy in). That was dropped: it made every install a manual procedure and gave the long name to the components used most.

An in-house component must not take a name the kit uses. Before adding one, check the kit's recipe list; prefer a descriptive name (`EntityCard`, not `Card`).

### Kit primitives underneath, named wrappers on top

The kit's components are primitives to compose. Where the same composition repeats, it gets an in-house wrapper with a one-line API, built from the kit's pieces rather than its own markup:

| Wrapper | Built on | Why it exists |
|---------|----------|---------------|
| `Callout` | `Alert`, `Alert:Title` | `type="warning"` picks the variant and the icon |
| `PageBreadcrumb` | `Breadcrumb` and its parts | Every page uses the same two-level shape; the Turbo frame target and truncation live in one place |
| `ResultsPagination` | `Pagination` and its parts, via `templates/shared/pagerfanta/default.html.twig` | Adds the result count and drives the links from a Pagerfanta pager |
| `FlashToast` | `Toast`, shown by the page's `Sonner` | Maps flash types to toast types and durations, supplies the coloured icon chip, and marks the toast so the toaster picks it up |
| `EmptyState` | `Empty` and its parts | `icon`, `message` and `subtitle` in one tag, with an optional `action` block |
| `StatusBadge` | `Badge` | Resolves a status string to its colour through `StatusColor`; optional icon and hover feedback. Keeps its own shape (`rounded-md`, more padding) over the kit's pill |
| `ProfitBadge` | `Badge` | Signs and formats a currency amount, green or red |
| `DataTable` | `Table` and its parts | Bordered frame with a title bar and a screen-reader caption; `head` and `body` blocks take kit rows or plain `<tr>` |

The common case is then identical at every call site and in every project, while an unusual case (a three-level breadcrumb, an alert with an action button) composes the kit's tags directly and looks the same because it is the same markup underneath.

Write a wrapper when a composition repeats; do not write parallel markup for something the kit already provides.

`EntityCard` is deliberately not a wrapper. It shares only its surface with the kit's `Card` (and uses the same `bg-card` and `border-border` tokens for it); the floating edit button, whole-card link, status stripe and colour schemes are its own, and the kit's `Card` clips overflow and pads through sub-parts in ways that would have to be overridden. It is a domain component.

### One toaster for flash messages

`base.html.twig` mounts a single `<twig:Sonner id="flash-container">`. Flash messages are rendered as `<twig:FlashToast>` inside it on page load, and Turbo Stream responses append them to `#flash-container` exactly as before. Toasts stack, pause while hovered, can be swiped away, and errors are announced assertively. They use the neutral popover surface with a coloured icon chip per type; the chip is in `FlashToast`, so a toast fired from JavaScript gets the kit's plain icon instead. The previous `closeable` controller and the `stimulus-use` package are removed.

### One form theme, built on the kit

`templates/form/shadcn_theme.html.twig` is the global form theme. It renders every field through kit components and replaces the Flowbite bundle's theme. The kit itself ships no Symfony form theme, so this file is ours to maintain.

A form whose fields should not look like standard controls opts out with `{% form_theme form with ['form_div_layout.html.twig'] only %}`. The inline edit form does this.

### One button

The kit's `Button` replaces the in-house one. It was tuned to the previous look (40px, semibold, soft shadow) and gained two variants the kit lacks: `destructive-solid` and `destructive-outline`.

### Tokens map onto the existing palette

`assets/styles/shadcn.css` defines the kit's role tokens (`--primary`, `--card`, `--border`, `--input`, …) as references to the app's existing gray and blue scales, for light and dark. Kit components therefore use role utilities (`bg-primary`, `bg-card`); everything else in the app keeps literal Tailwind classes. Both styles coexist in one template.

In-house components use the tokens too where one is an exact match (`border-border` for dividers and frames, `bg-popover` for the Turbo modal, `text-foreground` and `text-muted-foreground`), so those follow a theme change. A `focus-ring` utility in the same file gives in-house links and buttons the kit's focus ring.

The kit's install guide applies `border-border` to every element and a background to `body`. Those rules are scoped to kit markup (`[data-slot]`) so existing components are unaffected.

The guide also redefines Tailwind's radius scale (`rounded-sm` through `rounded-4xl`) in terms of one `--radius` value. That block is left out, so `rounded-*` keeps Tailwind's sizes for kit and existing markup alike.

### Keep what the kit does not replace

- **The Turbo modal** (ADR 007). `ConfirmDialog` is built on `ModalPanel`, whose heading (`#modal-title`) names the `<dialog>`. The kit's `Dialog` renders one `<dialog>` per trigger with content already in the page. FormFlow and ConfirmFlow depend on a single `<dialog>` filled from the server through the `modal` Turbo Frame, so `basic_modal`, `ModalPanel` and `ConfirmDialog` stay. The kit's `Dialog` and `AlertDialog` are for content that is already on the page.
- **Domain components**: `EntityCard`, `StatusIcon`, `KpiCard`, `Search`, `InlineEdit`, `ProductImage`, `SupplierDot`. `Search` composes kit pieces inside (`InputGroup` for the search box, `EmptyState`, `ResultsPagination`).

Small pieces with no logic of their own use the kit directly, with no wrapper: `Skeleton` (loading placeholders), `Spinner`, `Avatar`, `Kbd`, and `InputGroup` for an input with an icon or action inside it.

Native `title="…"` tooltips are left as they are; the kit's `Tooltip` is for content a native title cannot show.

`basic_popover_controller.js` and the `stimulus-popover` package were removed; nothing used them. Install the kit's `popover`, `hover-card` or `tooltip` recipe when one is needed.

The header user menu, previously a hand-written show/hide panel with its own `toggle` controller, is now the kit's `DropdownMenu`, which adds arrow-key navigation, Escape to close and focus return.

### Remove Flowbite

With forms and buttons on the kit, nothing needs Flowbite. The bundle, the JavaScript package, the datepicker and `@popperjs/core` are removed. The two side panels (sidebar menu, help panel) are kit `Sheet` components; the help panel keeps a small controller for its `?` shortcut and lazy loading. The `flowbite:*` icons remain; they are local SVG files served by UX Icons and load no Flowbite code.

### Installing or updating a recipe

```bash
symfony console ux:install <recipe> --kit=shadcn
```

The command asks before overwriting an existing file and shows the diff. It prints any Composer or importmap packages the recipe needs; install those separately.

No in-house file now shares a name with a kit recipe.

`ux:install` copies files with the modification time of the vendor source, which is older than the project's compiled template cache. When a recipe lands on a path that already had a template, Twig keeps serving the old compiled version and reports errors from it. Delete the cache directories afterwards: `rm -rf var/cache/dev var/cache/test`.

### Local changes to kit files

Updating a recipe offers to overwrite these; keep them when reviewing the diff:

| File | Change |
|------|--------|
| `Button` | 40px default and a size scale shifted up one step; semibold; shadow on solid and outline variants; `destructive-solid` and `destructive-outline`; `data-slot` moved into defaults so wrappers can override it |
| `Input`, `Textarea`, `NativeSelect` | 40px controls, `px-3`, `rounded-lg`, own text colour, 16px text on mobile |
| `Input`, `Textarea` | `data-slot` moved into defaults so `InputGroup:Input` and `InputGroup:Textarea` can override it; without that the group's focus ring never shows (kit bug) |
| `InputGroup` | 40px high, to match `Input` |
| `Label` | `data-slot` moved into defaults so `Field:Label` can override it |
| `AlertDialog` | `open` value rendered as `true`/`false`; an empty value made Stimulus open the dialog on load |
| `Alert` | `info`, `success` and `warning` variants added; `destructive` changed from a neutral card with red text to the same tinted style as the others |
| `sonner_controller.js` | A `toast` target, so a server-rendered toast added after connect (a Turbo Stream append) is moved into the list and activated; the kit only does this for toasts present at connect. Default surface changed from `bg-background` to `bg-popover`, so a toast stands off the page in dark mode. A press on a toast's close button or action link no longer starts a swipe; the swipe captured the pointer and the click never reached them (kit bug) |
| `Toast` | An optional `icon` block before the content, and the same `bg-popover` surface. The kit's template has no way to supply an icon |

The `Label`, `Input`/`Textarea` `data-slot`, `AlertDialog` and toast close-button changes are kit bugs worth reporting upstream.

## Differences in this application

The kit files were copied from the skeleton with the local changes above already applied. This application differs in the following ways:

- **Extra button variants.** `Button` also has `warning` (cancel, rewind and remove confirmations), `success` (approve a review) and `secondary-solid` (the solid gray button this application had as `secondary`; the kit's own `secondary` is a soft fill). Keep them when updating the recipe.
- **Date picker.** The kit's `date-picker`, `calendar` and `popover` recipes are installed, unmodified. `DatePickerType` (`src/Shared/UI/Http/Form/Type/`) renders through the `date_picker_widget` block of the form theme and submits a `Y-m-d` string from the calendar's hidden input. The order and purchase order date filters use it in place of the Flowbite datepicker.
- **Form theme.** The theme also covers `MoneyType` and `PercentType`, showing the symbol as an `InputGroup` addon, and `DatePickerType`.
- **Collapsible menu sections.** The sidebar's sections were opened by Flowbite's collapse. The `sidebar-active` controller now does it (`sidebar-active#toggle`), alongside the active-link state it already managed.
- **Dark header bar.** The header is dark in both modes, so the ghost buttons on it (menu, help, theme, sign in, user menu) pin their colours on the call site instead of following the theme tokens.
- **User menu.** The `DropdownMenu` has a Settings item as well as Sign Out.
- **`ProfitBadge`** is built on `Badge` but stays plain green or red text, not a tinted pill.
- **`toggle` controller.** Kept; the order item card still uses it.
- **No `/ui-sandbox`.** The reference page was not brought over. `tests/Shared/UI/KitComponentsFlowTest.php` covers the wrappers, form theme and layout on real pages instead.

## Consequences

### Positive

- A new interactive component is one command plus theming, with keyboard handling and ARIA already done, instead of a hand conversion.
- One form theme covers every field kind the app uses, with errors and help text linked to their controls.
- Colours for kit components are changed in one file.
- Flowbite's per-render initialisation and three packages are gone.
- The components are plain project files; there is no library to be locked into.

### Negative

- `symfony/ux-toolkit` is marked experimental. Recipes can change, and two bugs were found in the versions installed here.
- Upgrading a recipe means reconciling the local changes above by hand.
- The form theme is ours to extend. Range and colour inputs and collections are not covered yet.
- The kit `Button` does not shrink in a flex row and has a fixed height, so two `w-full` buttons side by side overflow (use `flex-1`), and padding alone does not make a button taller (add `h-auto`). Icons inside it are forced to 16px unless given a `size-*` class.
- In-house and kit components now share one directory, told apart only by name; `EntityCard` sits beside the kit's `Card`.
- Toasts no longer show a countdown bar.
- Two toast quirks in the kit are left as they are: toasts stay paused if one is dismissed while hovered, and the toast "dismissed" event fires after the toast has been removed.
- The other repositories that share this component set still use the old names, so templates copied between them need translating.

### Neutral

- Kit components use role tokens (`bg-primary`) while the rest of the app uses palette classes (`bg-blue-600`). This is deliberate: rewriting the kit to palette classes would make every upstream diff noise.
- Mobile inputs and selects use 16px text, larger than before, to avoid the iOS focus zoom.
- `twig-tailwind-extra` moved from 0.6 to 1.x; its `tailwind_merge` filter is unchanged and `tailwind_classes` is new.

## References

- [Symfony UX Toolkit](https://symfony.com/bundles/ux-toolkit/current/index.html)
- [Shadcn kit](https://ux.symfony.com/toolkit/kits/shadcn)
- ADR 007 (Turbo modal), ADR 008 (server-driven UI)
- `Docs/patterns/UI/Forms.md` — form theme usage
