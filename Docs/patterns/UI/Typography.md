# Typography Scale

Standardized typography classes used across SupplyMars. All text uses Tailwind's default sans-serif stack.

## Scale

| Role | Classes | Used In |
|------|---------|---------|
| Page / entity heading | `text-xl font-semibold` | Search `<h1>`, show page entity names, inline-edit display |
| Dialog title | `text-base font-semibold` | `Dialog.html.twig` |
| Section label | `text-xs font-medium uppercase tracking-wide text-gray-500` | Section headers in cards and detail pages |
| Body text | `text-sm text-gray-600 dark:text-gray-400` | Card content, description paragraphs |
| Metadata | `text-xs text-gray-500 dark:text-gray-500` | Timestamps, reference codes, secondary info |
| Financial unit value | `text-lg font-light` | Individual line prices in order/PO summaries |
| Financial total | `text-xl font-semibold` | Grand totals in order/PO summaries |
| KPI value | `text-2xl font-bold` | `KpiCard` primary metric |
| KPI label | `text-sm font-medium text-gray-500` | `KpiCard` title |

## Color Conventions

| Context | Light | Dark |
|---------|-------|------|
| Primary text | `text-gray-900` | `dark:text-white` |
| Secondary text | `text-gray-600` | `dark:text-gray-400` |
| Tertiary / muted | `text-gray-500` | `dark:text-gray-500` |
| Link text | `text-primary-600` | `dark:text-primary-400` |

## Guidelines

- **Entity headings** on show pages always use `text-xl font-semibold`. This applies to both inline-editable names (via `displayClass` prop on `<twig:InlineEdit>`) and static headings.
- **Subordinate headings** (e.g., a PO card nested within an order page) may use `text-base` to indicate hierarchy.
- **Section labels** use the uppercase tracking pattern consistently. Do not use `font-bold` or `text-sm` for section labels.
- **Financial values** follow a light/bold weight contrast: unit prices are `font-light`, totals are `font-semibold`.
- Always include dark mode text color variants (`dark:text-*`).

## Related

- [Surfaces](Surfaces.md) — Background and border conventions
- [UI README](README.md) — Server-driven UI overview
