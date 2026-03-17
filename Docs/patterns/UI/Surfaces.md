# Surface Hierarchy

SupplyMars uses a 7-layer surface system to create consistent depth perception across light and dark modes. Each layer serves a specific role in the visual hierarchy.

## Layers

| Layer | Purpose | Light | Dark |
|-------|---------|-------|------|
| 1. Page background | Base canvas | `bg-gray-100` | `dark:bg-gray-950` |
| 2. Primary surface | Cards, panels | `bg-white` | `dark:bg-gray-700/50` |
| 3. Elevated surface | Modals, dialogs | `bg-white` | `dark:bg-gray-800` |
| 4. Inset / well | KPI panels, subordinate content | `bg-gray-50` | `dark:bg-gray-900/60` |
| 5. Header / footer accent | Card footers, section accents | `bg-gray-50` | `dark:bg-gray-700/50` |
| 6. Control surface | Sort bars, toolbars | `bg-gray-50` | `dark:bg-gray-800` |
| 7. Hover state | Interactive row/card hover | `hover:bg-gray-50` | `dark:hover:bg-gray-800/50` |

## Border Conventions

| Context | Classes |
|---------|---------|
| Card border | `border border-gray-200 dark:border-gray-600` |
| Section divider (within card) | `border-t border-gray-200 dark:border-gray-600` |
| Dialog / modal border | `border border-gray-200 dark:border-gray-700` |
| Header nav border | `border-b border-gray-700/50` |
| Sidebar border | `border-r border-gray-200 dark:border-gray-700/50` |
| Footer border | `border-t border-gray-200 dark:border-gray-700` |
| Table row border | `border-b border-gray-100 dark:border-gray-700` |

## Card Wells

Use the **well** pattern for secondary or subordinate content nested inside a card. This creates a visually recessed area that separates it from the card's primary content.

```html
<div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900/50">
    <!-- Subordinate content: addresses, metadata blocks, embedded lists -->
</div>
```

Wells are appropriate for:
- KPI metric panels within dashboard cards
- Delivery addresses within order cards
- Quoted text (e.g., review body, ticket message)
- Nested metadata or configuration details
- Collapsed/expandable section content

Wells are **not** appropriate for:
- Primary card content (use the card surface itself)
- Interactive elements that need prominence (use the card surface or a bordered section)

## Dark Mode Tinted Backgrounds

For color-tinted surfaces (alerts, badges), use opacity on a mid-tone base rather than shade-based colors:

| Pattern | Example |
|---------|---------|
| Tinted background | `bg-{color}-500/10` |
| Tinted border | `inset-ring-{color}-500/20` |
| Tinted text | `text-{color}-400` |

This creates uniform visual intensity across all colors in dark mode.

## Guidelines

- Modals use Layer 3 (`bg-white` / `dark:bg-gray-800`) with `rounded-xl`, `shadow-2xl`, and a blurred backdrop (`backdrop-blur-[2px]`). Cards use Layer 2 (`bg-white` / `dark:bg-gray-700/50`).
- Wells (Layer 4) should be used sparingly. Overuse flattens the hierarchy.
- The page background (Layer 1) is set in the base layout and should not be overridden in content templates.
- Always include both light and dark mode classes for surfaces and borders.

## Related

- [Typography](Typography.md) — Text scale and color conventions
- [UI README](README.md) — Server-driven UI overview
