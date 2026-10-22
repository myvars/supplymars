# Styling: colour, surfaces and type

The classes in-house templates use for backgrounds, borders and text, so a new page matches the rest. Read when styling anything that is not a kit component.

**The dark-mode rule:** a kit role token (`bg-card`, `bg-popover`, `border-border`, `text-foreground`, `text-muted-foreground`) carries its own light and dark value, defined in `assets/styles/shadcn.css`. Every literal Tailwind colour class needs its `dark:` partner (`text-gray-600 dark:text-gray-400`). Prefer a token wherever it is an exact match; that keeps in-house components consistent with the kit's. The token mapping to the gray and blue scales is in `assets/styles/shadcn.css`.

## Surfaces

| Layer | Use | Classes |
|-------|-----|---------|
| Page | Base canvas | `bg-gray-100 dark:bg-gray-950` on `<body>`; `bg-gray-100 dark:bg-gray-900` on `<main>` (`base.html.twig`) |
| Primary surface | Cards, panels, tables | `bg-card` (white / gray-800 mixed 70% into gray-900) |
| Elevated surface | Modals, dialogs, menus | `bg-popover` (white / gray-800) |
| Accent | Table title bars and header rows | `bg-muted/50` (`DataTable`) |
| Control surface | Sort bar on index pages | `bg-gray-50 dark:bg-gray-800/80` (`Search`) |
| Recessed area | Content set back from its card, e.g. a review's body | `bg-gray-50 dark:bg-gray-800/50` (`review/_review_detail_card.html.twig`). No token; use sparingly, overuse flattens the hierarchy |
| Row hover | Table rows | `hover:bg-muted/50` |

- A page that needs a different background overrides the `main_class` block, not the body.
- Turbo modals (`Modal`) use `bg-popover`, `rounded-xl`, `shadow-2xl` and a blurred backdrop (`backdrop:backdrop-blur-[2px]`).
- `KpiCard` is the one card not on `bg-card`: `bg-white dark:bg-gray-800/40 dark:ring-1 dark:ring-white/[0.06]`.
- Other hovers: sidebar links `hover:bg-gray-100 dark:hover:bg-gray-800`; a clickable `EntityCard` `hover:shadow-md hover:brightness-[0.97] dark:hover:brightness-125`.

## Borders

`border-border` is gray-200 in light mode, white at 10% in dark.

| Where | Classes |
|-------|---------|
| Card, dialog, section divider in a card | `border border-border` / `border-t border-border` |
| Table rows, the sidebar `Sheet` | `border-b` / `border-r` (kit components default to the `border-border` colour) |
| Header | `border-b border-gray-700/50` (the header bar is dark in both modes) |
| Footer, sort bar, summary total rows | `border-gray-200 dark:border-white/[0.06]` |

## Tinted backgrounds in dark mode

Colour-tinted surfaces use opacity on a mid-tone rather than a darker shade, which keeps intensity even across colours.

| Component | Dark classes | Source |
|-----------|--------------|--------|
| Badges | `dark:bg-{color}-400/10 dark:text-{color}-400 dark:inset-ring-{color}-400/20` | `src/Shared/UI/Twig/StatusColor.php` |
| `Alert` / `Callout` | `dark:border-{color}-500/20 dark:bg-{color}-500/10 dark:text-{color}-400` | `templates/components/Alert.html.twig` |

## Type scale

All text uses Tailwind's default sans-serif stack.

| Role | Classes | Used in |
|------|---------|---------|
| Page / entity heading | `text-xl font-semibold` | Search `<h1>`, show-page entity names, `InlineEdit` via its `displayClass` prop |
| Subordinate heading | `text-base font-semibold` | A nested card on a detail page; dialog titles (`ModalPanel`) |
| Section label | `text-xs font-medium uppercase tracking-wide text-gray-500` | Section headers in cards and detail pages. Never `font-bold` or `text-sm` |
| Body text | `text-sm text-gray-600 dark:text-gray-400` | Card content, descriptions |
| Metadata | `text-xs text-gray-400 dark:text-gray-500` | Timestamps, reference codes |
| Price on an index card | `text-lg font-light` | Order, purchase order and supplier product cards |
| Summary total row | `font-medium` under a `border-t` | Order and purchase order price summaries |
| KPI value / label | `text-xl font-semibold tabular-nums sm:text-2xl` / section-label classes with `text-muted-foreground` | `KpiCard` |

## Text colours

| Role | Light | Dark |
|------|-------|------|
| Primary | `text-gray-900` | `dark:text-white` |
| Secondary | `text-gray-600` | `dark:text-gray-400` |
| Muted | `text-gray-500` | `dark:text-gray-500`, or `text-muted-foreground` (gray-500 / gray-400) |
| Link | `text-primary-600` | `dark:text-primary-400` |
| Active nav | `text-primary-700` on `bg-primary-50` | `dark:text-primary-300` on `dark:bg-primary-900/20` |
| Inactive nav | `text-gray-900` (child links `text-gray-700`) | `dark:text-gray-200` (child links `dark:text-gray-400`) |

The nav classes live in `assets/controllers/sidebar_active_controller.js`, which swaps them.

## Gotchas

- Templates pair primary text with `dark:text-white`, while the body default and `--foreground` are gray-100 (`dark:text-gray-100`). Unstyled text and `text-foreground` are therefore slightly softer than explicitly styled primary text. Follow the template you are next to.
- A colour that only has its light class looks right in light mode and is unreadable in dark. Check both with the theme toggle.

## Related

- [UI patterns](README.md)
- [ADR 012](../../adr/012-shadcn-ui-kit.md): the kit decision
