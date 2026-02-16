# UI/UX Audit Report — SupplyMars (Round 2)

**Date:** 2026-02-15
**Scope:** Full-stack UI audit — Twig templates, Twig components, Stimulus controllers, Tailwind CSS design system, external pattern benchmarking
**Methodology:** Documentation review (`Docs/`), full template inventory (165+ templates, 21 components, 23 Stimulus controllers), PHP component analysis (11 classes), CSS design token audit, external pattern comparison (Tailwind UI, Flowbite)
**Previous audit:** `REPORT_OLD.md` — all items resolved or declined. This report starts fresh with a post-resolution baseline.

---

## A) Visual System Overview

### How the UI Currently Feels

SupplyMars has matured into a **polished, consistent admin dashboard**. The Round 1 audit resolved foundational issues — breadcrumbs, sort header differentiation, sidebar active state, toast timing, divider standardization, icon consolidation, heading semantics, and `<dl>` adoption. The application now provides clear wayfinding and reliable feedback at every level.

The Card-based layout with status-aware highlights, KPI grids, and search-driven index pages creates a coherent operating environment. Dark mode is the clear hero — 14 of 21 components include explicit dark variants, and the custom color tokens (primary blue, 4 supplier palettes, danger rose) give the interface genuine visual identity.

### What Works Well

1. **Component consistency** — 21 Twig components used uniformly: Card (139 uses), KpiCard (66), StatusBadge (42), Search (28), Button (22). Pattern repetition creates genuine predictability.

2. **Dark mode** — Class-based (`dark:` prefix) with FOUC prevention, localStorage persistence, and full component coverage. Gray scale choices (`gray-900`/`gray-800`/`gray-700` backgrounds, `gray-100`/`gray-400` text) produce excellent contrast.

3. **Search pattern** — `Search.html.twig` handles debounced input, Turbo frame results, sticky sort headers, filter state awareness (yellow icon when active), pagination, and block-based extensibility. Used identically across 14+ index pages.

4. **Status color system** — `StatusColor::resolve()` maps 17 status values across 8 colors. StatusBadge, Card highlight borders, and ProfitBadge all feed from the same logic.

5. **Turbo/Stimulus integration** — Native `<dialog>` modals, 3-tier frame hierarchy (`body`/`modal`/`{model}-table`), view transitions with direction-aware animations and `prefers-reduced-motion` respect.

6. **Semantic HTML** — `<dl>` on financial summaries, `<nav aria-label="Breadcrumb">`, `role="alert"` on Alert, `sr-only` labels, skip-to-content link, `scope="col"` on table headers.

7. **Consolidated icon system** — 3 icon sets (`bi:*`, `flowbite:*`, `simple-icons:*`) with consistent visual weight.

### Where Consistency Exists

- **Index pages**: All 14+ index pages follow `<twig:Search>` → `<twig:SortLink>` → `<twig:Card>` pattern
- **Detail pages**: Breadcrumb + Card with status highlight + section dividers (`border-t dark:border-gray-600`)
- **Modals**: All create/edit/delete flows use `modal_base.html.twig` → `Dialog` → `FlowForm`
- **Typography labels**: Section headers consistently use `text-xs font-medium uppercase tracking-wide text-gray-500`
- **Dividers**: Standardized to `dark:border-gray-600`
- **Toasts**: Type-differentiated timing (3500ms success / 6000ms warning/danger)

---

## B) High-Impact Findings

### ~~B1. Dark Mode Backgrounds Use Shade-Based Instead of Opacity-Based Colors~~

**Resolved:** Alert dark backgrounds aligned to `bg-{color}-500/10` + `border-{color}-500/20` across all 4 variants. Toast unchanged (uses solid shades for icon badge — different pattern). Commit `5b41d09`.

<details><summary>Original finding</summary>

**Evidence:** `StatusBadge.php` uses shade-based dark backgrounds:
```
bg-green-400/10 dark:text-green-400 dark:inset-ring-green-400/20
```
This is already correct for StatusBadge. However, `Alert.php` uses a different approach:
```
dark:border-red-800/50 dark:bg-red-900/20 dark:text-red-400
```
The `bg-red-900/20` mixes shade + opacity, while Tailwind UI's canonical pattern uses `bg-red-500/10` (pure opacity on mid-tone base). The inconsistency means alerts and badges render with subtly different background intensity in dark mode.

**Reference:** Tailwind UI uses `bg-{color}-500/10` + `text-{color}-400` + `ring-{color}-500/20` consistently for all tinted surfaces in dark mode. This creates uniform intensity regardless of the color.

**Scope:** `Alert.php` (4 color variants), potentially `Toast.php` (3 color variants).
**Importance:** Low — subtle visual inconsistency.
**Effort:** Small — update color class strings in 2 PHP files.

**Recommendation:** Align Alert and Toast dark-mode backgrounds to use `bg-{color}-500/10` instead of `bg-{color}-900/20` for consistency with the badge system. Example: `dark:bg-red-900/20` → `dark:bg-red-500/10`.

</details>

---

### B2. No Formalized Typography Scale

**Evidence:** Typography is consistent within each context but not documented as a system. Current usage observed across templates:

| Role | Current Classes | Used In |
|------|----------------|---------|
| Page title | `text-xl font-semibold` | Search component heading |
| Entity heading | `text-xl font-semibold` or `text-lg font-semibold` | Show page `<h1>` |
| Dialog title | `text-base font-semibold` | `Dialog.html.twig` |
| Section label | `text-xs font-medium uppercase tracking-wide text-gray-500` | Section headers |
| Body text | `text-sm text-gray-600 dark:text-gray-400` | Card content |
| Metadata | `text-xs text-gray-500 dark:text-gray-500` | Timestamps, references |
| Financial values | `text-lg font-light` (prices) / `text-xl font-semibold` (totals) | Order/PO summaries |

The issue: entity headings inconsistently use `text-xl` vs `text-lg`. Some `<h1>` tags in show pages use `text-lg font-semibold` (e.g., product show) while others use `text-xl font-semibold` (e.g., order show). This creates variable heading prominence across detail pages.

**Reference:** Tailwind UI standardizes: `text-2xl font-bold` for page titles, `text-base font-semibold` for section/card titles, `text-xs font-medium uppercase tracking-wide` for labels.

**Scope:** 16+ show page headings, Search component heading.
**Importance:** Low-Medium — affects visual rhythm across detail pages.
**Effort:** Small — audit and normalize heading size to one consistent value.

**Recommendation:** Standardize entity headings to `text-xl font-semibold` across all detail pages. Document the typography scale in `Docs/patterns/UI/`.

---

### B3. Card Component Lacks Structured Section Pattern

**Evidence:** Card content sections are separated via ad-hoc `border-t` dividers applied in each calling template:
```twig
{# order/show.html.twig #}
<div class="border-t border-gray-200 pt-4 dark:border-gray-600">
```

This is repeated ~30 times across detail page templates. Each template manually adds divider classes between sections within a Card.

**Reference:** Tailwind UI cards use `divide-y divide-gray-200 dark:divide-gray-700` on a parent wrapper, which automatically adds dividers between direct child `<div>` elements. This eliminates manual border classes entirely.

**Scope:** All detail page Card templates (~20 files with section dividers).
**Importance:** Medium — reduces boilerplate and prevents divider drift.
**Effort:** Small-Medium — add `divide-y` support to Card component or adopt as convention.

**Recommendation:** Consider adding a `divided` prop to Card (or use `divide-y` as a convention in calling templates' content wrappers). This would replace ~30 manual `border-t` instances with automatic dividers. Example:
```twig
<twig:Card statusHighlight="..." divided>
    <div>Section 1</div>
    <div>Section 2</div>  {# divider auto-inserted #}
</twig:Card>
```

---

### ~~B4. Focus Indicators Use `focus:ring` Instead of `focus-visible:outline`~~

**Resolved:** All 6 filled Button variants migrated from `focus-visible:ring-2` to `focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-{color}`. Link variant unchanged (uses underline). Commit `5b41d09`.

<details><summary>Original finding</summary>

**Evidence:** Button component (`Button.php`) uses `focus:ring-4 focus:ring-*` patterns:
```php
'focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800'
```

The `focus:` pseudo-class triggers on both mouse click and keyboard navigation. This creates visible focus rings on every button click, which is visually noisy for mouse users.

**Reference:** Tailwind UI standardizes on `focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-{color}-600` which only shows focus indicators for keyboard navigation (better UX). Additionally, `outline` is more reliable than `ring` for focus indicators — `ring` uses `box-shadow` which can conflict with other shadows.

**Scope:** `Button.php` (7 variants), search input, form fields.
**Importance:** Medium — affects interaction polish for every button/link.
**Effort:** Small — update focus class strings in `Button.php`.

**Recommendation:** Migrate from `focus:ring-4 focus:ring-*` to `focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-*`. This is the modern Tailwind best practice and eliminates click-triggered focus rings.

</details>

---

### ~~B5. Modal Size API Uses Boolean Instead of Explicit Sizes~~ ✅

**Resolved:** Replaced `allowSmallWidth` boolean with `size` prop (`sm|md|lg|xl`) on `Dialog.html.twig`. Size communicated to singleton `<dialog>` via `data-modal-size` attribute, read by Stimulus controller in `frameLoaded()`. `ConfirmDialog` defaults to `sm`. Docs updated in `Docs/patterns/Turbo/Modals.md`.

---

## C) Systemic Improvements

### C1. Adopt Surface Hierarchy Convention

**What changes:** Establish a documented surface layering system for consistent depth perception across light and dark modes.

| Layer | Light | Dark |
|-------|-------|------|
| Page background | `bg-gray-50` | `dark:bg-gray-950` |
| Primary surface (cards) | `bg-white` | `dark:bg-gray-800` |
| Elevated surface (modals) | `bg-white` | `dark:bg-gray-800` |
| Inset/well content | `bg-gray-50` | `dark:bg-gray-900/50` |
| Header/footer accent | `bg-gray-50` | `dark:bg-gray-700/50` |
| Control surface (sort bar) | `bg-gray-100` | `dark:bg-gray-800` |
| Hover state | `hover:bg-gray-50` | `dark:hover:bg-gray-800/50` |

**Current state:** Mostly followed already. The sort header uses `bg-gray-100`/`dark:bg-gray-800`, cards use `bg-white`/`dark:bg-gray-800`, page background via base layout. The hierarchy exists implicitly but isn't documented.

**Where:** Add to `Docs/patterns/UI/` as design system reference.
**Risk:** Very low — documentation, no code changes.
**Effort:** Small.
**Value:** Prevents future drift and helps new developers make consistent surface choices.

---

### ~~C2. Add Sidebar Notification Badges for Actionable Counts~~ ✅

**Resolved:** Count badges added to 4 sidebar nav items: Moderation Queue (pending reviews), Rejected POs, Overdue Orders (last 30 days), and My Queue (open tickets in user's pools). Counts cached in Redis with separate keys — event-driven invalidation for reviews/POs/orders (`SidebarBadgeCacheInvalidator` listener), 5-min TTL for tickets (no domain events in Notes context). Zero DB queries on cache hit. My Queue also now excludes closed tickets by default when `myPools` is active. Commit `82ff978`.

---

### ~~C3. Migrate Button Focus to `focus-visible:outline`~~

**Resolved:** See B4. Commit `5b41d09`.

---

### ~~C4. Normalize Dark Mode Tinted Backgrounds~~

**Resolved:** See B1. Alert normalized; Toast unchanged (different pattern). Commit `5b41d09`.

---

### ~~C5. Consider `group-hover` for Card Links~~

**Resolved:** Added `group` class to Card's showLink `<a>` wrapper. Calling templates can now opt in to `group-hover:` on child elements. Commit `5b41d09`.

---

## D) External Pattern Insights

### D1. DescriptionList Component (Tailwind UI: Data Display)

**Pattern:** Tailwind UI description lists use `<dl>` with `divide-y` rows, responsive grid layout (`sm:grid sm:grid-cols-3 sm:gap-4`), and clear label/value separation. Labels: `text-sm font-medium text-gray-500`, values: `text-sm text-gray-900 sm:col-span-2`.

**Where it applies:** Entity detail pages — Product show, Supplier show, Customer show, Manufacturer show. Currently these use ad-hoc Card content with inconsistent label/value formatting.

**Why it helps:** A `DescriptionList` Twig component would standardize how entity attributes are displayed across all 10+ bounded contexts. The `divide-y` + grid pattern is more scannable than free-form card content for attribute display.

**Effort:** Medium — new component + gradual adoption across detail pages.
**Risk:** Low — additive, doesn't change existing pages until adopted.
**Recommendation:** **Consider for future** — the current card-based approach works well. A DescriptionList component would help most if new detail pages are being built. Not urgent for existing pages.

---

### D2. PageHeader Component (Tailwind UI: Headings)

**Pattern:** Tailwind UI page headings combine breadcrumb + heading + action buttons in a structured bar. Key technique: `md:flex md:items-center md:justify-between` with `min-w-0 flex-1` on the title area.

**Where it applies:** Detail pages currently embed breadcrumb and heading inside the Card or adjacent to it. A PageHeader would standardize the top-of-page navigation area.

**Current state:** The Breadcrumb component handles navigation context. Headings are inside cards. Action buttons (edit, status actions) are inside cards or as floating edit buttons.

**Recommendation:** **Defer** — the current Breadcrumb + Card heading approach works well. A PageHeader component would only add value if pages need more complex header layouts (e.g., multiple action buttons outside the card). Monitor for this need.

---

### D3. Timeline/Feed Pattern for Audit Logs (Flowbite/Tailwind UI: Feeds)

**Pattern:** Vertical timeline with left-side connector line (`border-s border-gray-200`), positioned dots (`absolute w-3 h-3 bg-gray-200 rounded-full -start-1.5`), timestamps, and descriptions.

**Where it applies:** The audit log currently appears as a modal table. A timeline view would be more natural for chronological status/stock changes. The ticket timeline (`note/ticket/show.html.twig`) already implements this pattern well.

**Key technique:**
```html
<ol class="relative border-s border-gray-200 dark:border-gray-700">
  <li class="mb-6 ms-4">
    <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white
                bg-gray-200 dark:border-gray-900 dark:bg-gray-700"></div>
    <time class="mb-1 text-sm text-gray-400">Feb 15, 2026</time>
    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Status Changed</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400">PENDING → PROCESSING</p>
  </li>
</ol>
```

**Effort:** Medium — template change for audit log display.
**Risk:** Low.
**Recommendation:** **Adopt when audit log UI is next touched.** The ticket timeline proves the pattern works in this codebase. Applying it to audit logs would significantly improve readability.

---

### D4. Gap-as-Border Pattern for KPI Grids (Tailwind UI: Stats)

**Pattern:** Instead of explicit borders between KPI cards, use `gap-px bg-gray-900/5 dark:bg-white/5` on the grid container with `bg-white dark:bg-gray-900` on each card. This creates pixel-perfect divider lines from the gap color showing through.

**Where it applies:** Dashboard KPI card grids (`reporting/show.html.twig`).

**Current state:** KPI cards use `gap-3` with individual card borders (`border border-gray-200 dark:border-gray-600`). This is fine but the gap-as-border technique creates a cleaner, more unified visual.

**Effort:** Very small — CSS class change on grid container + card elements.
**Risk:** Very low.
**Recommendation:** **Nice to have** — try on the dashboard and see if it looks better. The current bordered cards are solid.

---

### D5. Command Palette / Global Search (Tailwind UI: Navigation)

**Pattern:** A search-driven overlay (typically Cmd+K) providing quick navigation across the app. Uses `fixed inset-0 z-50` with centered search input and filtered results.

**Where it applies:** Quick navigation across bounded contexts — jumping to specific orders, products, customers by name/ID.

**Current state:** Navigation requires opening the sidebar drawer and clicking through sections.

**Effort:** High — requires global search endpoint, Stimulus controller, result ranking logic.
**Risk:** Medium — significant feature addition.
**Recommendation:** **Defer** — valuable for power users but a significant engineering effort. The sidebar navigation with active state highlighting serves current needs. Consider only if users report navigation friction with many entities.

---

### D6. Drawer/Slide-Over Panels (Tailwind UI: Overlays)

**Pattern:** Right-side slide-over panels for "quick view" and "quick edit" without full page navigation. `fixed inset-y-0 right-0 z-40 w-full max-w-md transform transition-transform`.

**Where it applies:** Viewing audit logs, order details, product info alongside main content.

**Current state:** Modals handle these use cases. The sidebar drawer uses Flowbite's Drawer API for navigation.

**Recommendation:** **Skip** — modals handle the same use cases. Adding a drawer-panel pattern would create two overlapping overlay paradigms. Only reconsider if modals become too constraining for wider content layouts.

---

### D7. Card Wells / Inset Sections (Tailwind UI: Layout)

**Pattern:** Secondary content areas within cards use an inset/well treatment: `rounded-lg bg-gray-50 p-4 dark:bg-gray-900/50`.

**Where it applies:** Nested content within cards — e.g., delivery address section within order card, supplier list within product card.

**Current state:** Some cards use section dividers to separate nested content. The well pattern would provide an alternative for content that deserves visual subordination.

**Effort:** Very small — CSS class pattern, no component needed.
**Risk:** Very low.
**Recommendation:** **Adopt as a convention** — document in pattern guide. Use for clearly secondary content like embedded addresses, metadata blocks, or collapsed section content.

---

### D8. Dot Separator for Inline Metadata (Tailwind UI: Headings)

**Pattern:** Instead of pipe (`|`) or comma separators for inline metadata, use a small dot SVG:
```html
<div class="flex items-center gap-x-2 text-xs text-gray-500">
  <span>March 17, 2026</span>
  <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 fill-current"><circle cx="1" cy="1" r="1"/></svg>
  <span>John Doe</span>
</div>
```

**Where it applies:** Card metadata lines showing date + user, status + count, or similar inline data.

**Current state:** Some templates use ` · ` text characters, others use separate `<p>` elements for metadata.

**Recommendation:** **Nice to have** — a polished touch for metadata lines. Low priority.

---

### ~~D9. Error Page Enhancement (Flowbite: Error Pages)~~

**Resolved:** All 4 error templates (403, 404, 500, generic) updated with prominent numeric codes (`text-7xl`/`lg:text-9xl` in orange), responsive bold headings, and "Back to Colony HQ" Button component. Mars-themed copy preserved. Commit `5b41d09`.

---

### ~~D10. ConfirmDialog Warning Icon (Flowbite: CRUD Delete)~~

**Resolved:** Added `bi:exclamation-triangle-fill` (h-12 w-12, red) centered above the message block in `ConfirmDialog.html.twig`. Commit `5b41d09`.

---

### D11. Button Outline-Danger Variant (Flowbite: CRUD Update)

**Pattern:** Flowbite uses an outlined danger button (`text-red-600 border border-red-600 hover:bg-red-600 hover:text-white`) for delete actions that appear alongside save buttons in update forms. The filled danger button is too visually dominant when placed next to a primary save button.

**Where it applies:** Update modals where both save and delete actions are available — currently handled by the `before_submit` block in `FlowForm.html.twig`.

**Current state:** Button component has 7 variants (primary, secondary, alternative, success, danger, warning, link). No outlined danger variant exists. Delete links in update forms use the `link` variant or custom styling.

**Effort:** Small — add one variant to `Button.php`.
**Risk:** Very low — additive, no existing usage affected.
**Recommendation:** **Consider** — add a `danger-outline` variant with `text-red-600 border border-red-600 bg-transparent hover:bg-red-600 hover:text-white`. Useful for forms that need both save and delete actions without two competing filled buttons. Lower priority than other items.

---

## E) Component Enhancement Opportunities

### ~~E1. Card `divided` Prop~~ — Declined

**Decision:** **Skip** — `divide-y` creates unwanted dividers in practice. Card content structures vary too much: order show mixes header/date/section children at the same level; category/supplier cards use `-mx-3 -mb-3` negative-margin bottom links incompatible with `divide-y`; financial `<dl>` dividers need inner control. The manual `border-t` approach gives needed per-section control. Stop condition met.

---

### ~~E2. Modal `size` Prop~~ ✅

**Resolved:** See B5 resolution above. Implemented via `data-modal-size` attribute system with `data-[modal-size=*]:` Tailwind variants on `<dialog>`.

---

### E3. FinancialSummary Partial

**Candidate:** Extract the financial breakdown pattern (`<dl>` with label/value rows, divider before total) from `order/show.html.twig` and `_po_card.html.twig`.

**Current state:** 2 instances. Both now use `<dl>` semantics (from Round 1 D3 resolution).

**Decision:** **Defer** — only 2 instances. Extract if a third appears (invoice, quote, credit note).

---

### ~~E4. DataTable Default Row Styling~~

**Resolved:** Default striped/hover/border row classes applied to `<tbody>` via `[&>tr]:` child selectors in `DataTable.html.twig`. Manual `rowClasses` removed from `reporting/show.html.twig`. Commit `5b41d09`.

---

## F) Quick Wins (1-3 Days)

### ~~F1. Normalize Alert Dark Mode Backgrounds~~ ✅

**Resolved:** See B1. Commit `5b41d09`.

---

### ~~F2. Migrate Button Focus to `focus-visible:outline`~~ ✅

**Resolved:** See B4. Commit `5b41d09`.

---

### ~~F3. Add `group` Class to Card showLink~~ ✅

**Resolved:** See C5. Commit `5b41d09`.

---

### ~~F4. Add Default Row Classes to DataTable~~ ✅

**Resolved:** See E4. Commit `5b41d09`.

---

### F5. Document Typography Scale

Create a typography scale reference in `Docs/patterns/UI/` documenting the standardized heading/body/label sizes used across the application.

**Files:** New file `Docs/patterns/UI/Typography.md`

---

### F6. Document Surface Hierarchy

Create a surface layering reference in `Docs/patterns/UI/` documenting background/border conventions for cards, modals, wells, and control surfaces.

**Files:** New file `Docs/patterns/UI/Surfaces.md`

---

### ~~F7. Add Warning Icon to ConfirmDialog~~ ✅

**Resolved:** See D10. Commit `5b41d09`.

---

### ~~F8. Enhance Error Pages~~ ✅

**Resolved:** See D9. Commit `5b41d09`.

---

## G) Phased Improvement Plan (PR-Sized)

### ~~Phase 0 — Polish & Standards~~ ✅

**Completed:** Commit `5b41d09`. 12 files changed. Also fixed `bi:currency-pound` → `bi:hash` on product number icons.

1. ~~Normalize Alert dark mode backgrounds (F1)~~
2. ~~Migrate Button focus to `focus-visible:outline` (F2)~~
3. ~~Add `group` class to Card showLink (F3)~~
4. ~~Add default row classes to DataTable (F4)~~
5. ~~Add warning icon to ConfirmDialog (F7)~~
6. ~~Enhance error pages with numeric code + CTA button (F8)~~

---

### ~~Phase 1 — Component API Improvements~~ ✅

**PR 1a: Modal Size Variants** — Complete. `size` prop on `Dialog.html.twig` (sm/md/lg/xl), `ConfirmDialog` defaults to `sm`. Data-attribute driven sizing via Stimulus controller. `allowSmallWidth` removed (had no callers). Docs updated.

**PR 1b: Card Divided Sections** — Declined. `divide-y` creates unwanted dividers given varied card content structures. Stop condition met.

---

### Phase 2 — Information Architecture (1-2 PRs, opportunistic)

**PR 2a: Sidebar Notification Badges** ✅ — Complete. Redis-cached count badges on 4 nav items (Moderation Queue, Rejected POs, Overdue Orders, My Queue). Event-driven invalidation for 3 of 4; short TTL for tickets. My Queue excludes closed tickets. Commit `82ff978`.

**PR 2b: Documentation**
- Typography scale (F5)
- Surface hierarchy (F6)
- Update `Docs/patterns/UI/README.md` with references
- Files: New docs files

**Stop conditions:**
- ~~Sidebar badges: Skip if repository queries create noticeable latency on nav render.~~ — Redis cache ensures sub-ms reads. No latency concern.
- ~~Card divided: Skip if `divide-y` creates unwanted dividers on empty blocks or conditional sections.~~ — Skipped (stop condition met).

---

## H) Questions & Observations

No blocking questions. All proposed changes can proceed independently.

**Non-blocking observations:**

1. **Container queries** — Tailwind CSS v4 supports `@container` queries which could benefit the Turbo Frame architecture (content width varies depending on sidebar state). Worth investigating for future responsive improvements, but viewport breakpoints work well currently.

2. **Tick icon normalization** — The recent change from `bi:check-circle-fill` to `bi:check-lg` on pricing cost cards aligned them with inline edit patterns. Verify that `StatusIcon.php` still uses `bi:check-circle-fill` for ACCEPTED/ACTIVE/VERIFIED status icons — this is intentional (semantic "status confirmed" vs "action completed").

3. **Toast stacking** — Toasts use `fixed top-5 right-5` positioning. Multiple simultaneous toasts will overlap rather than stack. This is only an issue during rapid batch operations. Not a current problem but worth noting if batch actions are added.

4. **Form theme** — The Flowbite form bundle handles form field styling. The custom override (`bundles/TalesFromADevFlowbiteBundle/form/custom_form_theme.html.twig`) is minimal. If form styling diverges from the rest of the design system, this is where to intervene.

5. **Breadcrumb depth** — The Breadcrumb component supports 2 levels (parent + current). If the app adds deeper hierarchies (e.g., Category > Subcategory > Product), the component would need extension. Not needed currently.
