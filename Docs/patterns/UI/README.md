# UI patterns

How the server-rendered UI is put together and where each kind of piece goes. Read when adding a component, a controller or a form field. The architecture decision is [ADR 008](../../adr/008-server-driven-ui-architecture.md); the UI kit decision is [ADR 012](../../adr/012-shadcn-ui-kit.md).

## When to use what

| Need | Use | Rule |
|------|-----|------|
| A general-purpose primitive (button, field, dialog, dropdown, tabs, tooltip) | Shadcn kit component, `<twig:Button>` etc. | Kit first. If it is missing, `symfony console ux:install <recipe> --kit=shadcn`; do not hand-build one. |
| The same composition of kit components in several places | In-house wrapper (`Callout`, `StatusBadge`, `DataTable`, …) | Build the wrapper from the kit's pieces, never parallel markup. |
| A domain-shaped piece of UI (`EntityCard`, `KpiCard`, `InlineEdit`, `SupplierDot`) | In-house component | Descriptive name. Never a name the kit uses. |
| Behaviour in the browser (drag and drop, uploads, chart formatting, keyboard shortcuts) | Stimulus controller | Check the existing ones and the kit recipes first; see [Stimulus controllers](StimulusControllers.md). |
| Data input and validation | Symfony form, rendered through the global theme | See [Forms](Forms.md). |
| Partial page updates, modals | Turbo Frames and Streams | See [Turbo](../Turbo/README.md). |

The wrapper list and button variants are in the UI kit section of [CLAUDE.md](../../../CLAUDE.md); the local changes to kit files are recorded in [ADR 012](../../adr/012-shadcn-ui-kit.md).

## Components

Most in-house components are anonymous Twig components: a template with `{% props %}` and no PHP class. Ten have a PHP class in `src/Shared/UI/Twig/Components/`, because they compute something (icons, colours, defaults, image URLs): `Callout`, `EntityCard`, `FlashToast`, `InlineEdit`, `PageBreadcrumb`, `ProductImage`, `ProfitBadge`, `StatusBadge`, `StatusIcon`, `SupplierDot`. Add a class only when a template would need logic.

There is no component gallery page. `tests/Shared/UI/KitComponentsFlowTest.php` checks the kit-based wrappers, form addons, flash toasts, side panels and user menu as real pages render them; extend it when you change one.

## File locations

| What | Where |
|------|-------|
| Component templates (kit and in-house) | `templates/components/` |
| Component PHP classes | `src/Shared/UI/Twig/Components/` |
| Stimulus controllers | `assets/controllers/` (shared helpers in `assets/lib/`) |
| Kit role tokens (`--card`, `--border`, …), `focus-ring` | `assets/styles/shadcn.css` |
| Tailwind v4 config (`@theme` colour scales, custom variants) | `assets/styles/app.css` |
| Global form theme | `templates/form/shadcn_theme.html.twig` |
| Form types / form models / mappers | `src/{Context}/UI/Http/Form/Type/`, `Model/`, `Mapper/` |
| JavaScript dependencies | `importmap.php` |

## Related

- [Styling](Styling.md): colour, surface and type conventions
- [Forms](Forms.md): the form theme, field kinds and gotchas
- [Stimulus controllers](StimulusControllers.md): every controller and what it is for
- [Turbo](../Turbo/README.md): frames, modals and streams
