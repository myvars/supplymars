# Surface Hierarchy

Surfaces and borders use the kit's role tokens, defined in `assets/styles/shadcn.css` and mapped to the app's gray scale. A token carries both its light and dark value, so a surface built on one needs no `dark:` class. Literal Tailwind classes are used only where no token is an exact match.

## Layers

| Layer | Purpose | Classes | Light | Dark |
|-------|---------|---------|-------|------|
| 1. Page background | Base canvas | `bg-gray-100 dark:bg-gray-950` on `<body>`; `bg-gray-100 dark:bg-gray-900` on `<main>` | gray-100 | gray-950 / gray-900 |
| 2. Primary surface | Cards, panels, tables | `bg-card` | white | gray-800 mixed 70% into gray-900 |
| 3. Elevated surface | Modals, dialogs, menus | `bg-popover` | white | gray-800 |
| 4. Accent | Table title bars and header rows | `bg-muted/50` | gray-100 at 50% | gray-700 at 50% |
| 5. Control surface | The sort bar on index pages | `bg-gray-50 dark:bg-gray-800/80` (with its own `border-gray-200 dark:border-white/[0.06]`) | gray-50 | gray-800 at 80% |
| 6. Hover state | Table rows | `hover:bg-muted/50` | gray-100 at 50% | gray-700 at 50% |

Other hover treatments: sidebar links use `hover:bg-gray-100 dark:hover:bg-gray-800`; a clickable `EntityCard` uses `hover:shadow-md hover:brightness-[0.97] dark:hover:brightness-125`.

`KpiCard` is the one card that does not use `bg-card`: it is `bg-white dark:bg-gray-800/40` with `dark:ring-1 dark:ring-white/[0.06]`.

## Border Conventions

`border-border` is gray-200 in light mode and white at 10% in dark mode.

| Context | Classes |
|---------|---------|
| Card border | `border border-border` |
| Section divider (within card) | `border-t border-border` |
| Dialog / modal border | `border border-border` |
| Table row border | `border-b` (kit components take the `border-border` colour by default) |
| Sidebar border | `border-r` (the kit `Sheet`, same default colour) |
| Header nav border | `border-b border-gray-700/50` (the header is dark in both modes) |
| Footer border | `border-t border-gray-200 dark:border-white/[0.06]` |

## Recessed Areas

There is no dedicated "well" token. Where content needs to sit back from its card, use `bg-gray-50` with a literal dark class such as `dark:bg-gray-800/50`. Use it sparingly; overuse flattens the hierarchy.

## Dark Mode Tinted Backgrounds

Colour-tinted surfaces use opacity on a mid-tone base rather than a darker shade, which keeps the intensity uniform across colours:

| Component | Dark classes | Source |
|-----------|--------------|--------|
| Badges | `dark:bg-{color}-400/10 dark:text-{color}-400 dark:inset-ring-{color}-400/20` | `src/Shared/UI/Twig/StatusColor.php` |
| Alerts / `Callout` | `dark:border-{color}-500/20 dark:bg-{color}-500/10 dark:text-{color}-400` | `templates/components/Alert.html.twig` |

## Guidelines

- Modals use `bg-popover` with `rounded-xl`, `shadow-2xl` and a blurred backdrop (`backdrop-blur-[2px]`). Cards use `bg-card`.
- Prefer a role token over a literal gray wherever one is an exact match. It is what keeps in-house components consistent with the kit's.
- The page background is set in the base layout. A page that needs a different one overrides the `main_class` block, not the body.
- A surface built on literal classes needs both its light and dark class.

## Related

- [Typography](Typography.md) — Text scale and color conventions
- [UI README](README.md) — Server-driven UI overview
- [ADR 012](../../adr/012-shadcn-ui-kit.md) — The kit and its colour tokens
