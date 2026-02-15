# UI/UX Audit Report — SupplyMars

**Date:** 2026-02-15
**Scope:** Twig templates, Twig components, shared layouts, Stimulus controllers, Tailwind CSS usage
**Methodology:** Documentation review, full template inventory, component analysis, external pattern benchmarking

> **Status:** Phase 1c complete + B4 fix. ~~Strikethrough~~ marks resolved items. Canonical dark-mode divider: `dark:border-gray-600`. Sidebar active state uses JS (`sidebar_active_controller.js` + `turbo:frame-load@window`) instead of Twig. Sort header differentiated via `Search.html.twig` wrapper (`5e719c6`). Breadcrumb component replaces BackLink on all detail pages (`b7b384f`). DataTable component extracted from dashboard (`6f07925`). Button link variant fixed (conditional base classes).

---

## A) Visual System Overview

### How the UI Currently Feels

SupplyMars presents as a **well-structured, functional admin dashboard** with a dark-mode-first aesthetic. The overall impression is of a system built by someone who understands both the backend architecture and UI component patterns. The Card-based layout with status highlights, KPI grids, and search-driven index pages creates a coherent operating environment.

The visual language is **consistent enough to feel intentional** — the same Card, StatusBadge, KpiCard, and Search patterns repeat across all bounded contexts, creating predictable navigation. The dark mode is the clear hero: dark backgrounds with well-chosen gray scales, supplier-specific color coding, and status-aware left-border highlights give the interface a polished, modern feel.

That said, the interface leans toward **information density over visual breathing room**. Some pages (especially detail views like `order/show.html.twig`) pack substantial information into tight vertical space, which is functional but can feel compressed.

### What Works Well

1. **Component system** — 20 Twig components (`templates/components/`) with clear, single-responsibility APIs. Card alone is used 71 times, StatusBadge 42 times, KpiCard 40 times. This creates genuine consistency, not just pattern repetition.

2. **Dark mode implementation** — Class-based (`dark:` prefix) with localStorage persistence and FOUC prevention via inline script in `base.html.twig:4-8`. 14 of 20 components include explicit dark mode variants. The gray scale choices (`gray-900`/`gray-800`/`gray-700` backgrounds, `gray-100`/`gray-400` text) produce excellent contrast.

3. **Search pattern** — `Search.html.twig` is a strong abstraction. Debounced input, Turbo frame-based results, embedded Turbo Streams for count updates, filter state awareness (yellow icon when active), and block-based extensibility. Used 14 times identically across contexts.

4. **Status color system** — `StatusColor::resolve()` (`src/Shared/UI/Twig/StatusColor.php`) maps domain statuses to colors consistently. StatusBadge, Card highlight borders, and ProfitBadge all feed from the same color logic. 17 status values mapped across 8 colors.

5. **Turbo/Stimulus integration** — Modal system via native `<dialog>`, Turbo frame hierarchy (`body`, `modal`, `{model}-table`), and smart layout detection in `modal_base.html.twig` is sophisticated and well-documented.

6. **Custom color tokens** — `app.css:11-77` defines primary (blue), danger (rose), and four supplier-specific palettes (purple, amber, teal, rose) with full 50-950 shade scales. This gives the supplier purchase order cards genuine visual differentiation.

7. **View transitions** — Direction-aware slide animations (`app.css:127-175`) with `prefers-reduced-motion` respect is a polished touch.

8. **Accessibility baseline** — Skip-to-content link (`base.html.twig:32`), `sr-only` labels on icon buttons, `role="alert"` on Alert, `aria-label` on Card edit buttons, `focus-visible:ring` patterns, native `<dialog>` element (automatic focus trapping/inert background).

### Where Consistency Exists

- **Index pages**: All 14+ index pages use the same `<twig:Search>` → `<twig:Card>` with `<twig:SortLink>` header pattern
- **Detail pages**: Card with status highlight, section dividers (`border-t border-gray-200 dark:border-white/10`), uppercase tracking-wide section labels
- **Modals**: All create/edit/delete flows use the same `modal_base.html.twig` → `Dialog` → `FlowForm` chain
- **Navigation**: Consistent sidebar drawer with collapsible sections, all links use `data-turbo-frame="body"` and `data-action="basic-drawer#close"`
- **Typography labels**: Section headers consistently use `text-xs font-medium uppercase tracking-wide text-gray-500`

---

## B) High-Impact Issues (Ranked)

### ~~B1. Sort Header Bar Lacks Visual Separation from List Items~~ ✅

~~**Evidence:** In every index page (e.g., `catalog/product/index.html.twig:12-21`, `order/index.html.twig:12-21`, `purchasing/purchase_order/index.html.twig:12-22`), sort headers are wrapped in `<twig:Card>` — the same component used for list items below. There is no visual distinction between the header row and the data rows.~~

~~**UX Impact:** Users must parse the first card to determine whether it's a sort control or a data item. In a list of many cards, the header blends in. This is a scanning/wayfinding issue — the eye has no anchor point to separate controls from content.~~

~~**Scope:** All 14+ search index pages.~~
~~**Importance:** High — affects every list view in the application.~~
~~**Effort:** Low — change the sort header Card styling or use a different surface.~~

**Resolved:** Sort wrapper moved into `Search.html.twig` with distinct control-surface styling (`bg-gray-100`/`dark:bg-gray-800`, border, no shadow, `sticky top-0 z-10`). Removed `<twig:Card>` from all 13 index page sort blocks.

---

### ~~B2. No Breadcrumb or Page Context Indicator~~ ✅

~~**Evidence:** Detail pages (e.g., `order/show.html.twig`, `catalog/product/show.html.twig`) rely solely on `<twig:BackLink>` at the bottom of the page. There is no breadcrumb trail or header indicating the current location within the application hierarchy. The page title block (e.g., `{% block title %}Order Details{% endblock %}`) appears only in the browser tab, not in the visible page content.~~

~~**UX Impact:** Users who land on a detail page (via direct link, bookmark, or deep navigation) have no visible context about where they are. The sidebar drawer is closed by default and doesn't show active state. The only orientation cue is the BackLink at the page bottom — which requires scrolling past all content to find.~~

~~**Scope:** All 16 show/detail pages across all contexts.~~
~~**Importance:** High — fundamental wayfinding issue.~~
~~**Effort:** Medium — needs a shared partial or component, plus integration into base layout or each detail page.~~

**Resolved:** `Breadcrumb.html.twig` component added with 2-level trail (Section > Current Page). Replaced `BackLink` on all 19 detail pages (13 standard show pages + 6 product sub-pages). `BackLink` component deleted.

---

### ~~B3. Dashboard Tables Use Raw HTML Instead of Component Patterns~~ ✅

~~**Evidence:** `reporting/show.html.twig:43-72` and `reporting/show.html.twig:108-137` contain inline `<table>` elements with hand-written Tailwind classes for striped rows, hover states, and responsive overflow. These are the only tables in the application — every other list uses Card-based layouts.~~

~~**UX Impact:** Visual inconsistency within the reporting context. The table styling (`odd:bg-white even:bg-gray-50 ... dark:odd:bg-gray-900 dark:even:bg-gray-800`) is well-done but not reusable. If tables are needed elsewhere, the pattern would be duplicated with potential drift.~~

~~**Scope:** 2 tables in dashboard, potential growth in reporting.~~
~~**Importance:** Medium — contained to reporting but growing.~~
~~**Effort:** Low-Medium — extract a Table component or shared partial.~~

**Resolved:** `DataTable.html.twig` component extracted with `title` prop and `head`/`body` blocks. Both dashboard tables refactored to use the component. Row classes extracted to a shared `rowClasses` variable.

---

### ~~B4. Button Component Missing `px` on `link` Variant~~ ✅

~~**Evidence:** `Button.html.twig:3-4` applies base classes including `py-2` to all variants. The variant-specific classes in `Button.php` add `px-4` for colored variants but the `link` variant typically needs different horizontal padding. All Button variants share the same font-semibold, which may be too heavy for inline text links.~~

~~**UX Impact:** Minor visual inconsistency. Link-styled buttons may appear with mismatched padding compared to actual `<a>` elements used elsewhere. The `font-semibold` on link variant creates heavier-than-expected inline links.~~

~~**Scope:** 34 Button usages.~~
~~**Importance:** Medium-Low.~~
~~**Effort:** Low — adjust variant classes in `Button.php`.~~

**Resolved:** Split base classes in `Button.html.twig` — link variant drops `font-semibold`, `py-2`, `rounded-lg`, `active:scale-[0.98]`; uses `font-medium` instead. Added `hover:underline underline-offset-2` and focus-visible styling to link variant in `Button.php`.

---

### ~~B5. Inconsistent Section Divider Patterns~~ ✅

~~**Evidence:** Compare:~~
~~- `order/show.html.twig:62`: `border-t border-gray-200 ... dark:border-white/10`~~
~~- `note/ticket/_ticket_card.html.twig:47`: `border-t border-gray-200 pt-3 dark:border-gray-700`~~
~~- `shared/form_flow/update.html.twig:13`: `<hr class="my-6 h-px border-0 bg-gray-200 dark:bg-gray-700">`~~
~~- `_footer.html.twig:28`: `border-gray-200 dark:border-gray-700/50`~~

~~Four different approaches to horizontal dividers: `border-white/10`, `border-gray-700`, `bg-gray-200 dark:bg-gray-700`, and `border-gray-700/50`. These create slightly different visual weights in dark mode.~~

~~**UX Impact:** Subtle visual inconsistency. Users won't consciously notice, but the overall polish is reduced.~~

~~**Scope:** Across all templates.~~
~~**Importance:** Low-Medium.~~
~~**Effort:** Low — standardize to one dark-mode border token.~~

**Resolved:** Standardized to `dark:border-gray-600` across all templates and components.

---

### ~~B6. Missing Loading/Skeleton States for Turbo Frames~~ — Declined

~~**Evidence:** `app.css` contains no skeleton/loading patterns. The only loading feedback is `turbo-frame[busy]` opacity reduction (implied by Turbo defaults) and the modal loading template slot in `Modal.html.twig:42-46`. Dashboard KPI cards (`reporting/show.html.twig`) load via Turbo frame morph but show no skeleton while data loads.~~

~~**UX Impact:** On slower connections, KPI cards and search results flash from empty to populated with no intermediate state. This creates perceived jank and uncertainty about whether the page is working.~~

~~**Scope:** All Turbo frame content (search results, dashboard, modals).~~
~~**Importance:** Medium — especially for dashboard which is data-heavy.~~
~~**Effort:** Medium — needs skeleton component and integration points.~~

**Declined:** Turbo's morph refresh handles transitions cleanly without additional loading states. CSS-based busy indicators (opacity dim, progress bars) add visual noise rather than improving the experience. No change needed.

---

### ~~B7. Menu Sidebar Has No Active State Indicator~~ ✅

~~**Evidence:** `_menu.html.twig` contains 9 navigation sections with no mechanism to highlight the currently active page. All nav items use identical classes: `text-gray-900 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800`. There is no `is_active` check, no `aria-current`, no visual differentiation for the current route.~~

~~**UX Impact:** When the sidebar opens, users cannot see which section they're currently in. Combined with the missing breadcrumbs (B2), this means there's no orientation mechanism at all.~~

~~**Scope:** Sidebar navigation (global).~~
~~**Importance:** Medium — sidebar is not always visible (drawer pattern), but important when it is.~~
~~**Effort:** Low — add route matching and conditional styling.~~

**Resolved:** `sidebar_active_controller.js` highlights active nav item via URL matching on `turbo:frame-load@window`. Includes `aria-current="page"`, auto-opens active section dropdown.

---

### ~~B8. Toast Auto-Close Timing Is Very Aggressive~~ ✅

~~**Evidence:** `Toast.html.twig:7`: `data-closeable-auto-close-value: 1200` — toasts dismiss after 1.2 seconds. The toast itself shows a timer bar animation (`duration-[1200ms]`).~~

~~**UX Impact:** 1.2 seconds is barely enough time to read a short success message, let alone a longer warning or error. Users performing rapid actions may never see the feedback. For danger/warning toasts, this is especially problematic — the message may vanish before the user processes the severity.~~

~~**Scope:** All flash messages application-wide.~~
~~**Importance:** Medium — affects feedback loop for every action.~~
~~**Effort:** Low — increase duration, differentiate by type.~~

**Resolved:** `Toast::getDuration()` returns 3500ms (success) / 6000ms (warning/danger). Timer bar uses inline `style` for dynamic duration.

---

## C) Systemic Improvements

### ~~C1. Standardize Section Dividers~~ ✅

~~**What changes:** Define a single border color token for section dividers in `app.css` (e.g., `--color-divider: var(--color-gray-200)` / dark: `var(--color-gray-700/50)`). Use one consistent pattern: `border-t border-divider` across all templates.~~

~~**Where:** `app.css` (add token), then find-and-replace across templates that use `border-gray-200 dark:border-white/10` or `dark:border-gray-700` variants.~~

~~**Risk:** Low — purely cosmetic normalization.~~
~~**Effort:** Small (1-2 hours).~~
~~**Value:** Eliminates visual inconsistency across detail pages, footers, and form dividers.~~

**Resolved:** Standardized to `dark:border-gray-600` via direct class replacement (no CSS custom property needed). Applied to templates and `Card.php`.

---

### ~~C2. Differentiate Sort Header from List Cards~~ ✅

~~**What changes:** Add a visual distinction for the sort header bar. Options: (a) remove Card wrapping and use a simpler `div` with lighter background, (b) add a Card variant prop like `variant="header"` with distinct styling (e.g., `bg-gray-50 dark:bg-gray-800/50` with no shadow), or (c) add subtle bottom border accent.~~

~~**Where:** `Search.html.twig` (the `sort` block default wrapper), all 14+ index page `sort` blocks.~~

~~**Risk:** Low — visual only, no behavioral change.~~
~~**Effort:** Small (2-3 hours).~~
~~**Value:** High — every list page becomes easier to scan.~~

**Resolved:** See B1. Went with option (a) — replaced Card with a styled `div` in `Search.html.twig`, removed Card+div wrappers from all 13 index pages.

---

### ~~C3. Add Breadcrumb Component~~ ✅

~~**What changes:** Create a `Breadcrumb.html.twig` component that renders a lightweight trail (e.g., `Orders > Order #000123`). Include it in detail pages above or inside the main Card. The component should accept an array of `{label, href}` pairs plus a current-page label.~~

~~**Where:** New component in `templates/components/`, used in all `show.html.twig` templates. Consider integrating into the base layout flow or as a block in `base.html.twig`.~~

~~**Risk:** Low — additive, doesn't change existing behavior.~~
~~**Effort:** Medium (half day for component + integration across 16 detail pages).~~
~~**Value:** High — solves the wayfinding gap (B2) across all detail views.~~

**Resolved:** See B2. `Breadcrumb.html.twig` component created, `BackLink` replaced on all 19 detail pages and deleted.

---

### ~~C4. Extend Toast Duration and Differentiate by Type~~ ✅

~~**What changes:** Increase default auto-close from 1200ms to 3000-4000ms for success, 5000ms+ for warning/danger. Pass the duration as a type-dependent value from `Toast.php` rather than hardcoding in the template. Consider making danger toasts non-auto-closing (require manual dismiss).~~

~~**Where:** `Toast.html.twig` (update `data-closeable-auto-close-value`), `Toast.php` (add duration logic), `closeable_controller.js` (no change needed — already reads value).~~

~~**Risk:** Very low — improves feedback without changing behavior.~~
~~**Effort:** Small (1-2 hours).~~
~~**Value:** Medium — every user action gets more reliable feedback.~~

**Resolved:** See B8.

---

### ~~C5. Add Active State to Sidebar Navigation~~ ✅

~~**What changes:** Add route-matching logic to `_menu.html.twig` that highlights the current section. Use Symfony's `app.request.attributes.get('_route')` to match against nav item routes. Apply visual differentiation: `bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300` for active items, plus `aria-current="page"`.~~

~~**Where:** `_menu.html.twig`.~~

~~**Risk:** Low — additive styling.~~
~~**Effort:** Small-Medium (2-4 hours, needs route matching logic for section groups).~~
~~**Value:** Medium — improves orientation when sidebar is open.~~

**Resolved:** See B7. Implemented via Stimulus controller (not Twig route matching) for Turbo compatibility.

---

### ~~C6. Extract Dashboard Table as Shared Partial or Component~~ ✅

~~**What changes:** Extract the repeated table pattern from `reporting/show.html.twig` into either a Twig component (`DataTable.html.twig`) or a shared partial. The component should accept: title, column headers, rows (iterable), and a row template block.~~

~~**Where:** New `templates/components/DataTable.html.twig` or `templates/shared/_data_table.html.twig`, refactored from `reporting/show.html.twig:43-72` and `108-137`.~~

~~**Risk:** Low — refactor of existing working code.~~
~~**Effort:** Small-Medium (3-4 hours).~~
~~**Value:** Medium — reusable for future reporting pages, eliminates duplication.~~

**Resolved:** See B3. Template-only component with `title` prop and `head`/`body` blocks (no PHP class needed).

---

### ~~C7. Add Skeleton Loading States for Key Turbo Frames~~ — Declined

~~**What changes:** Create a `Skeleton.html.twig` component (or CSS-only approach via `animate-pulse` placeholders) for: (a) KPI card skeletons in dashboard, (b) search result placeholders. Use Turbo's `[busy]` attribute or the `loading` template slot in frames.~~

~~**Where:** `templates/components/Skeleton.html.twig` (new), integrated into `Search.html.twig` and `reporting/show.html.twig`.~~

~~**Risk:** Low — additive, progressive enhancement.~~
~~**Effort:** Medium (half day).~~
~~**Value:** Medium — perceived performance improvement, especially on dashboard.~~

**Declined:** See B6. Turbo morph handles transitions cleanly; additional loading states add noise.

---

### C8. Normalize Card Content Density with Consistent Inner Spacing ✅

**Resolved.** Audited all 56 card templates — spacing tiers are mostly intentional (compact index cards use tighter spacing, detail cards use more generous spacing). Fixed three genuine inconsistencies: standardized section divider padding to `pt-4` on detail cards (was `pt-3` on ticket/review summary), standardized section label margins to `mb-2` (was `mb-3`/`mb-1.5`), and fixed 9 remaining `dark:border-gray-700` → `dark:border-gray-600` on card section dividers missed during Phase 0. 10 files changed.

---

### C9. Consolidate Icon Library Sources

**What changes:** The codebase uses icons from 8+ different icon sets via Symfony UX Icons: `flowbite:*`, `bi:*` (Bootstrap), `ri:*` (Remix), `lets-icons:*`, `mingcute:*`, `hugeicons:*`, `mynaui:*`, `bxs:*` (Boxicons), `mdi:*` (Material), `ic:*`, `clarity:*`, `simple-icons:*`, `icon-park-solid:*`. While Symfony UX Icons handles loading, the visual style varies (filled vs outlined, rounded vs sharp corners, line weights).

Audit icon usage and prefer 1-2 primary sets (suggest: `bi:*` for general UI icons, `flowbite:*` for navigation/status) with others only for specialized cases.

**Where:** All templates. Primarily impacts `_menu.html.twig`, `StatusIcon.php`, and various card templates.

**Risk:** Low-Medium — visual change, needs side-by-side comparison.
**Effort:** Medium (half day for audit, 1 day for replacement).
**Value:** Low-Medium — improves visual cohesion, reduces cognitive load of mixing icon styles.

---

### ~~C10. Add Page-Level Heading to Detail Pages~~ ✅

~~**What changes:** Detail pages like `order/show.html.twig` put the main heading (`Order #000123`) inside a Card. There is no page-level `<h1>` outside the card that provides structural context. Add a lightweight page header area (entity type + ID) above the card content, or ensure the Card's heading is semantically an `<h1>`.~~

~~**Where:** All `show.html.twig` templates (currently use `<h5>` for the main entity heading — semantically incorrect if it's the page's primary heading).~~

~~**Risk:** Low — semantic improvement.~~
~~**Effort:** Small (2-3 hours).~~
~~**Value:** Medium — improves accessibility (heading hierarchy) and page structure for screen readers.~~

**Resolved:** See F3. Changed `<h5>` → `<h1>` on detail pages.

---

## D) External Pattern Insights

### D1. Stacked List with Sticky Header (Tailwind UI: Application UI / Lists / Stacked Lists)

**Pattern:** Tailwind UI's stacked list pattern uses a distinct header row with `sticky top-0` positioning, a subtle background difference from list items, and consistent column alignment. The header uses `text-xs font-semibold uppercase text-gray-500` on a `bg-gray-50` surface.

**Where it applies:** All 14+ Search index pages in SupplyMars.

**Why it helps:** Directly addresses issue B1 (sort headers indistinguishable from data cards). The sticky behavior would keep column headers visible during scroll, improving long-list navigation.

**Effort:** Small — modify the `sort` block wrapper in `Search.html.twig`.
**Risk:** Low — CSS-only change.
**Recommendation:** **Adapt** — use the surface differentiation concept (lighter/distinct background for header) without adopting the full stacked list HTML structure, since Card-based lists work well for SupplyMars's card layout.

---

### D2. Page Heading with Breadcrumbs (Tailwind UI: Application UI / Headings / Page Headings)

**Pattern:** Tailwind UI page headings combine breadcrumbs, a primary heading, and optional action buttons in a structured header bar. Breadcrumbs use `text-sm text-gray-500` with chevron separators.

**Where it applies:** All detail/show pages and potentially index pages.

**Why it helps:** Addresses issue B2 (no breadcrumb or page context). The pattern provides a reusable header component that combines navigation context with page identity.

**Effort:** Medium — new component + integration.
**Risk:** Low — additive.
**Recommendation:** **Use selectively** — implement breadcrumbs for detail pages only (where navigation context is most needed). Index pages already have clear headings via the Search component. Avoid over-engineering with full page heading bars that would conflict with the existing Card-first layout.

---

### D3. Description List (Tailwind UI: Application UI / Data Display / Description Lists)

**Pattern:** Tailwind UI description lists use `<dl>` with alternating grid rows, clear label/value separation via `dt`/`dd` pairs, and `grid-cols-[auto_1fr]` layout. Labels are `text-sm font-medium text-gray-500`, values are `text-sm text-gray-900`.

**Where it applies:** Order summary sections (`order/show.html.twig:62-108`), ticket detail metadata (`note/ticket/_ticket_card.html.twig:48-68`), pricing cascade cards.

**Why it helps:** The ticket card already uses `<dl>` with this pattern well (`_ticket_card.html.twig:48`). The order summary uses a `div`-based layout for the financial breakdown. Using `<dl>` semantics consistently would improve accessibility (screen readers announce term/definition pairs) and visual consistency.

**Effort:** Small per instance.
**Risk:** Very low — semantic improvement.
**Recommendation:** **Adopt incrementally** — use `<dl>` for structured key-value data wherever it occurs naturally. Don't retrofit where the current layout works well (e.g., the order financial summary is already clear).

---

### D4. Stats/KPI Cards with Trend Indicators (Tailwind UI: Application UI / Data Display / Stats)

**Pattern:** Tailwind UI stat cards place the metric label above the value, with a small trend badge (up/down arrow + percentage) positioned to the right of the value. They use `rounded-lg bg-white shadow` containers in a responsive grid.

**Where it applies:** Dashboard KPI cards (`reporting/show.html.twig`), already using `<twig:KpiCard>`.

**Why it helps:** SupplyMars's KpiCard already closely follows this pattern. The main difference is layout: Tailwind UI places trend indicators inline/right of the value, while KpiCard stacks them vertically below. The vertical stack works well for centered cards but the inline variant is more compact.

**Effort:** Small — optional layout tweak to KpiCard.
**Risk:** Low.
**Recommendation:** **Keep current approach** — the existing KpiCard is already a strong implementation of this pattern. The centered vertical layout works well in the 3-column grid. No change needed unless horizontal KPI cards are desired later.

---

### D5. Badge/Pill Patterns (Tailwind UI: Application UI / Elements / Badges)

**Pattern:** Tailwind UI badges use `inset-ring` (ring inside the element) for subtle bordered pills with tinted backgrounds. The pattern: `bg-{color}-50 text-{color}-700 ring-1 ring-inset ring-{color}-600/20`.

**Where it applies:** StatusBadge, ProfitBadge components.

**Why it helps:** SupplyMars already uses `inset-ring` on ProfitBadge and StatusBadge. The implementation closely matches Tailwind UI's reference pattern. This is a validation that the current approach is aligned with best practices.

**Effort:** None.
**Risk:** None.
**Recommendation:** **Already adopted** — no changes needed. The existing badge implementation is excellent.

---

### ~~D6. Empty States with Action (Tailwind UI: Application UI / Feedback / Empty States)~~ ✅

~~**Pattern:** Tailwind UI empty states include an icon, message, and a primary action button (e.g., "Create your first X"). The button provides a direct escape hatch from the empty state.~~

~~**Where it applies:** `EmptyState.html.twig` component.~~

~~**Why it helps:** The current EmptyState has icon + message + optional subtitle, but no action slot. Adding an optional action button (or block) would allow "No products found — Create one" patterns.~~

~~**Effort:** Very small — add a block to EmptyState.~~
~~**Risk:** Very low.~~
~~**Recommendation:** **Adopt** — add an optional `action` block to `EmptyState.html.twig`. Low effort, high polish.~~

**Resolved:** `{% block action %}{% endblock %}` added to `EmptyState.html.twig`.

---

### D7. Feed/Timeline Pattern (Tailwind UI: Application UI / Data Display / Feeds)

**Pattern:** Tailwind UI feeds use a vertical timeline with connected dots, avatar + content pairs, and timestamp metadata. The connector line runs through left-aligned dots.

**Where it applies:** Ticket conversation timeline (`note/ticket/show.html.twig`).

**Why it helps:** The ticket show page likely already uses some timeline pattern for conversation messages. The Tailwind UI feed pattern provides a clean reference for consistent message threading.

**Effort:** Small if already implemented similarly, medium if restructuring.
**Risk:** Low.
**Recommendation:** **Review and align** — compare current ticket timeline implementation against this pattern. Adopt if it improves clarity; skip if current implementation is already clean.

---

### D8. Form Layouts with Side Labels (Tailwind UI: Application UI / Forms / Form Layouts)

**Pattern:** Tailwind UI form layouts use a two-column grid where labels sit on the left and fields on the right at desktop breakpoints, collapsing to stacked on mobile.

**Where it applies:** Modal forms via FlowForm.

**Why it helps:** The current FlowForm uses `space-y-4 md:space-y-6` for vertical stacking. Since forms render in modals (50% width), horizontal label-field layout would likely be too cramped. The current vertical stack is appropriate for the modal context.

**Effort:** N/A.
**Risk:** N/A.
**Recommendation:** **Avoid** — the current stacked form layout is correct for modal-width forms. Side labels would create cramped layouts within the dialog constraint.

---

## E) Component Extraction Opportunities

### ~~E1. DataTable Component~~ ✅

~~**Candidate pattern:** Striped, hoverable HTML tables with header, body, and responsive overflow wrapper. Currently exists as inline HTML in `reporting/show.html.twig` (2 instances).~~

~~**Evidence:**~~
~~- `reporting/show.html.twig:43-72`: "Today's Latest Orders" table~~
~~- `reporting/show.html.twig:108-137`: "Today's Top Products" table~~
~~- Both share identical: outer wrapper classes, thead styling, tbody row styling, link patterns~~

~~**Why a component improves it:** Eliminates 50+ lines of duplicated Tailwind classes. As reporting grows (per ADR-005's two-layer reporting strategy), more tables are likely. A component ensures consistent table styling and reduces template size.~~

~~**Decision:** **Extract** — create `DataTable.html.twig` with props for `title`, `columns` (block), `rows` (block). Keep it simple — a styled wrapper, not a full abstraction.~~

**Resolved:** See B3. Extracted as `DataTable.html.twig` with `title` prop and `head`/`body` blocks.

---

### ~~E2. PageHeader / Breadcrumb Component~~ ✅

~~**Candidate pattern:** Page-level heading with breadcrumb trail and optional action buttons.~~

~~**Evidence:** Currently absent. Every detail page uses ad-hoc heading inside Card content. `<twig:BackLink>` at page bottom is the only navigation aid.~~

~~**Why a component improves it:** Solves wayfinding (B2, B7). A `PageHeader` component could render breadcrumbs + heading + optional actions in a consistent bar above the main Card content.~~

~~**Decision:** **Extract** — create `Breadcrumb.html.twig` as a lightweight navigation trail. Consider a `PageHeader.html.twig` only if the breadcrumb + heading + actions pattern repeats enough to warrant it. Start with Breadcrumb alone.~~

**Resolved:** `Breadcrumb.html.twig` extracted as decided. `BackLink` component replaced and deleted. `PageHeader` deferred — not needed with current breadcrumb approach.

---

### ~~E3. SectionDivider Component~~ ✅

~~**Candidate pattern:** Horizontal divider between content sections.~~

~~**Evidence:** 4 different divider patterns across templates (see B5).~~

~~**Why a component improves it:** Guarantees consistent visual weight across all contexts.~~

~~**Decision:** **Skip** — a component is over-engineering for a single CSS class. Instead, standardize the Tailwind class pattern in documentation and find-and-replace. Use `border-t border-gray-200 dark:border-gray-700/50` as the canonical pattern.~~

**Resolved:** Followed the "Skip" recommendation — standardized via find-and-replace to `dark:border-gray-600`. No component needed.

---

### E4. FinancialSummary Partial

**Candidate pattern:** Key-value financial breakdown (subtotal, shipping, VAT, total) with final total in bold + divider.

**Evidence:**
- `order/show.html.twig:78-105`
- `purchasing/purchase_order/_po_card.html.twig` (similar structure)

**Why a Twig partial improves it:** The layout (flex justify-between, gap-6, divider before total) is identical. A partial would take an array of `{label, value}` pairs plus a total.

**Decision:** **Defer** — only 2 instances currently. Extract if a third appears (e.g., invoice, quote). Document the pattern for consistency.

---

## F) Quick Wins (1-3 Days)

### ~~F1. Increase Toast Duration~~ ✅

~~Change `data-closeable-auto-close-value` from `1200` to `3500` for success, `6000` for warning/danger in `Toast.html.twig` and `Toast.php`. Users will actually read the feedback.~~

~~**Files:** `templates/components/Toast.html.twig`, `src/Shared/UI/Twig/Components/Toast.php`~~

---

### ~~F2. Differentiate Sort Header Visually~~ ✅

~~In `Search.html.twig`, change the sort block's default `<twig:Card>` wrapper to use a lighter surface:~~
~~- Light: `bg-gray-50 border-gray-200` (vs Card's `bg-white`)~~
~~- Dark: `bg-gray-800/50 border-gray-700/50` (vs Card's `bg-gray-800`)~~
~~- Remove `shadow-sm` from the sort header~~
~~- Consider `sticky top-0 z-10` for scroll persistence~~

~~**Files:** Either modify the `sort` block default in `Search.html.twig`, or add a Card variant prop.~~

**Note:** Used `bg-gray-100`/`dark:bg-gray-800` (slightly adjusted from proposed values). Sticky behavior included. 14 files changed.

---

### ~~F3. Fix Heading Semantics on Detail Pages~~ ✅

~~Change `<h5>` headings in detail pages to `<h1>` (they are the primary page heading). Example: `order/show.html.twig:17` uses `<h5>` for "Order #000123" — this should be `<h1>` with the same visual styling. No visual change needed, just the element.~~

~~**Files:** All `show.html.twig` templates (16 files).~~

**Note:** Only 2 files had `<h5>` as primary heading (`order/show.html.twig`, `purchasing/purchase_order/_po_card.html.twig`).

---

### ~~F4. Standardize Divider Classes~~ ✅

~~Find and replace all dark-mode divider variants to use one canonical pattern:~~
~~`border-gray-200 dark:border-gray-700/50`~~
~~Replace: `dark:border-white/10`, `dark:border-gray-700` (without opacity), `bg-gray-200 dark:bg-gray-700`.~~

~~**Files:** ~20 templates across contexts.~~

**Note:** Canonical token adjusted to `dark:border-gray-600` per user preference (brighter in dark mode).

---

### ~~F5. Add `action` Block to EmptyState~~ ✅

~~Add an optional block below the subtitle in `EmptyState.html.twig`:~~
~~`{% block action %}{% endblock %}`~~
~~This enables "No results found — Create one" patterns without changing existing usages.~~

~~**Files:** `templates/components/EmptyState.html.twig`~~

---

### ~~F6. Add `aria-current="page"` to Active Menu Items~~ ✅

~~In `_menu.html.twig`, add route-based active detection. For each nav link, check if the current route matches and apply `aria-current="page"` plus a visual indicator (e.g., left border accent or background tint).~~

~~**Files:** `templates/_menu.html.twig`~~

**Note:** Implemented via `sidebar_active_controller.js` (URL-based matching) rather than Twig route matching, for Turbo frame compatibility.

---

## G) Phased Improvement Plan (PR-Sized)

### ~~Phase 0 — Quick Polish~~ ✅ (`d884dd8`)

~~**Scope:** Low-risk, zero-behavior-change improvements.~~

**Changes (all complete):**
1. ~~Increase toast auto-close to 3500ms (success) / 6000ms (warning/danger) — `Toast.html.twig`, `Toast.php`~~
2. ~~Fix heading semantics: `<h5>` → `<h1>` on detail pages (2 files, no visual change)~~
3. ~~Standardize divider classes to `dark:border-gray-600` (~20 files + `Card.php`)~~
4. ~~Add `action` block to EmptyState component~~
5. ~~Add active state to sidebar nav — `sidebar_active_controller.js` + `turbo:frame-load@window`~~

**Implementation notes:**
- Divider token adjusted from proposed `dark:border-gray-700/50` to `dark:border-gray-600` (brighter, per user preference)
- Sidebar active state uses Stimulus controller (not Twig) for Turbo frame compatibility
- Only 2 files had `<h5>` primary headings (not 16 as estimated)
- 22 files changed, 260 insertions, 85 deletions

---

### Phase 1 — Systemic Normalization (2-3 PRs, 3-5 days)

~~**PR 1a: Sort Header + Search Polish**~~ ✅ (`5e719c6`)
~~- Differentiate sort header bar from list items (new surface treatment)~~
~~- Consider sticky header behavior for long lists~~
~~- Files: `Search.html.twig`, optionally `Card.php` (new variant)~~

~~**PR 1b: Breadcrumb Component + Page Context**~~ ✅ (`b7b384f`)
~~- Create `Breadcrumb.html.twig` component~~
~~- Integrate into all 16 detail pages~~
~~- Consider removing bottom BackLink where Breadcrumb provides the same navigation~~

**Implementation notes:** 2-level breadcrumb (Section > Current Page). Applied to 19 templates (13 show pages + 6 product sub-pages). `BackLink` component fully replaced and deleted. 22 files changed.

~~**PR 1c: Dashboard Table Extraction**~~ ✅ (`6f07925`)
~~- Extract DataTable component from `reporting/show.html.twig`~~
~~- Apply to both existing dashboard tables~~
~~- Document component API~~

**Implementation notes:** Template-only component (`DataTable.html.twig`) with `title` prop and `head`/`body` blocks. Row classes extracted to a shared `rowClasses` variable in the calling template. 2 files changed, 1 new file.

**Tests to add/update:**
- Add a browser flow test that verifies breadcrumb rendering on a detail page (e.g., `OrderFlowTest`)
- Verify Search component renders sort header with new styling
- Verify DataTable component renders correctly in dashboard

**Validation approach:**
- Cross-browser visual check (Safari, Chrome, Firefox)
- Mobile responsive check (sort header, breadcrumbs)
- Dark mode verification for all new/changed components
- Run full test suite: `make test`

**Stop conditions:** If breadcrumb integration creates layout issues in specific contexts, make it optional per-page rather than forcing it. If DataTable abstraction becomes too complex for 2 instances, use a simpler shared partial instead.

---

### Phase 2 — Optional Refinements (2-3 PRs, opportunistic)

**PR 2a: Icon Consolidation**
- Audit all icon usage across templates
- Choose 1-2 primary icon sets (suggest: Bootstrap Icons + Flowbite)
- Replace outliers where visually equivalent icons exist in primary sets
- Document icon conventions

~~**PR 2b: Loading/Skeleton States**~~ — Declined
~~- Create Skeleton component (animated pulse placeholders)~~
~~- Add to dashboard KPI grid (most visible loading delay)~~
~~- Add to search results frame~~
~~- Consider a `<turbo-frame>` CSS approach (`turbo-frame[busy]` selectors)~~

**Declined:** Turbo morph handles transitions cleanly; additional loading states add visual noise. See B6.

~~**PR 2c: Card Inner Spacing Normalization**~~
✅ Completed. Fixed genuine inconsistencies (section divider padding, label margins, dark border colors) across 10 templates rather than forcing artificial spacing tiers. Existing spacing tiers are intentional per content type.

**Tests to add/update:**
- ~~Skeleton: Visual test or screenshot comparison (no behavioral test needed)~~ Declined
- Icons: No tests needed — visual change only
- ~~Spacing: Existing flow tests cover layout; manual visual review required~~ Done — visual-only changes

**Validation approach:**
- ~~Side-by-side before/after screenshots for spacing changes~~ Done
- ~~Performance check for skeleton states (ensure no layout shift)~~ N/A
- ~~Dark mode verification for all changes~~ Done — border colors verified

**Stop conditions:**
- Icon consolidation: Stop if primary icon sets lack equivalent icons for specialized cases (e.g., `lets-icons:refund-back`). Keep the outlier rather than losing semantic clarity.
- Spacing: Stop if normalization creates visual issues in dense contexts (e.g., pricing cascade cards). Per-context overrides are acceptable.
- ~~Skeletons: Stop if the perceived benefit is minimal (fast API responses make skeletons flash). Consider removing skeleton if response is typically < 200ms.~~ Declined — Turbo morph handles this well.

---

## H) Questions

No blocking questions. All proposed changes can proceed with the information gathered during this audit.

**Non-blocking observations for consideration:**

1. The sidebar navigation uses Flowbite's `data-collapse-toggle` for collapsible sections. This requires Flowbite JS initialization on every navigation (handled in `app.js`). If Flowbite is ever removed, these collapses would need a Stimulus replacement. Worth noting but not blocking.

2. The `container mx-auto` wrapper in `base.html.twig:33` combined with `md:px-3` creates a contained layout. The `max-w-(--breakpoint-xl)` constraint in Search and footer means content maxes out at ~1280px. On very wide screens, this leaves substantial empty space. This is a deliberate choice and works well — no change recommended.

3. The custom form theme (`templates/bundles/TalesFromADevFlowbiteBundle/form/custom_form_theme.html.twig`) is minimal (5 lines). The Flowbite form bundle handles most styling. If form styling issues arise, they'll likely need to be addressed in this file or via a more comprehensive custom theme. Not a current problem.
