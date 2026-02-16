# UI/UX Audit Report — SupplyMars (Round 3)

**Date:** 2026-02-16
**Scope:** Full-stack UI audit — Twig templates, Twig components, Stimulus controllers, Tailwind CSS design system, external pattern benchmarking
**Methodology:** Documentation review (`Docs/`), full template inventory (165+ templates, 21 components, 23 Stimulus controllers), PHP component analysis (11 classes), CSS design token audit, external pattern comparison (Tailwind UI, Catalyst, Flowbite)
**Previous audits:** `REPORT_OLD1.md` (Round 1), `REPORT_OLD2.md` (Round 2) — all items resolved or declined. This report starts fresh with a post-Round-2 baseline.

---

## A) Visual System Overview

### How the UI Currently Feels

SupplyMars has matured into a **polished, well-structured admin dashboard** with a coherent dark-mode-first aesthetic. Two rounds of systemic improvement have resolved foundational issues — breadcrumbs, sort header differentiation, sidebar active state + badges, toast timing, divider standardization, icon consolidation, heading semantics, `<dl>` adoption, KPI gap-as-border, modal size API, typography normalization, surface hierarchy documentation, and error page enhancement.

The visual language is **consistent and intentional**. The same Card, StatusBadge, KpiCard, and Search patterns repeat across all bounded contexts. Custom color tokens (primary blue, 4 supplier palettes, danger rose) give the interface genuine visual identity. The Turbo/Stimulus integration — native `<dialog>` modals, direction-aware view transitions, smart frame hierarchy — is sophisticated and well-documented.

That said, post-resolution drift has introduced a new class of issues: **Tailwind class inconsistency in newer templates** (reporting pages, legal pages, auth pages) that weren't caught by the standardization passes of Rounds 1-2. Additionally, **accessibility gaps in interactive components** (SortLink, flash messages, KpiCard trends) represent the primary remaining area for improvement.

### What Works Well

1. **Component system** — 21 Twig components used uniformly: Card (139 uses), KpiCard (66), StatusBadge (42), Search (28), Button (22). Pattern repetition creates genuine predictability.

2. **Dark mode** — Class-based (`dark:` prefix) with FOUC prevention (`base.html.twig:4-8`), localStorage persistence, and full component coverage. Gray scale choices (`gray-900`/`gray-800`/`gray-700` backgrounds, `gray-100`/`gray-400` text) produce excellent contrast.

3. **Search pattern** — `Search.html.twig` handles debounced input, Turbo frame results, sticky sort headers, filter state awareness (yellow icon when active), pagination, and block-based extensibility. Used identically across 14+ index pages.

4. **Status color system** — `StatusColor::resolve()` (`src/Shared/UI/Twig/StatusColor.php`) maps 17 status values across 8 colors. StatusBadge, Card highlight borders, and ProfitBadge all feed from the same logic.

5. **Turbo/Stimulus integration** — Native `<dialog>` modals with size variants, 3-tier frame hierarchy (`body`/`modal`/`{model}-table`), view transitions with direction-aware slide animations and `prefers-reduced-motion` respect.

6. **Semantic HTML** — `<dl>` on financial summaries, `<nav aria-label="Breadcrumb">`, `role="alert"` on Alert, `sr-only` labels on icon buttons, skip-to-content link (`base.html.twig:32`), `scope="col"` on table headers.

7. **Consolidated icon system** — 3 icon sets (`bi:*`, `flowbite:*`, `simple-icons:*`) with consistent visual weight.

8. **Design system documentation** — Typography scale (`Docs/patterns/UI/Typography.md`), surface hierarchy (`Docs/patterns/UI/Surfaces.md`), Stimulus controller guide (`Docs/patterns/UI/StimulusControllers.md`), and comprehensive FormFlow/Turbo pattern docs.

### Where Consistency Exists

- **Index pages**: All 14+ index pages follow `<twig:Search>` → sticky sort header → `<twig:Card>` pattern
- **Detail pages**: Breadcrumb + Card with status highlight + `border-t dark:border-gray-600` section dividers
- **Modals**: All create/edit/delete flows use `modal_base.html.twig` → `Dialog` (sm/md/lg/xl) → `FlowForm`
- **Typography labels**: Section headers consistently use `text-xs font-medium uppercase tracking-wide text-gray-500`
- **Toasts**: Type-differentiated timing (3500ms success / 6000ms warning/danger)
- **KPI grids**: Gap-as-border pattern (`gap-px overflow-hidden rounded-lg bg-gray-900/5 dark:bg-white/5`)
- **Sidebar**: Active state via Stimulus controller, 5 Redis-cached badge counts, collapsible sections

---

## B) High-Impact Issues (Ranked)

### ~~B1. Dark Border Standardization Drift in Newer Templates~~ ✅

**Status:** Resolved in Phase 0. All `dark:border-gray-700` instances replaced with `dark:border-gray-600`.

<details><summary>Original finding</summary>

**Evidence:** The Round 1 audit standardized all dark-mode borders to `dark:border-gray-600`. However, templates added or modified since then have reverted to `dark:border-gray-700`. Specific instances:

- `templates/components/DataTable.html.twig:3` — `dark:border-gray-700`
- `templates/reporting/product_sales.html.twig:27,45,76` — chart containers, table borders
- `templates/reporting/order_summary.html.twig:18,32` — chart borders
- `templates/reporting/customer_insights.html.twig:17,28,46` — chart and table borders
- `templates/reporting/customer_geographic.html.twig:28,34,63`
- `templates/reporting/customer_segments.html.twig:24,30,62`
- `templates/reporting/overdue_orders.html.twig:17,50`
- `templates/reporting/po_item_performance.html.twig:17,51`
- `templates/audit/log.html.twig:8`, `templates/audit/product_history.html.twig:16,25`
- `templates/catalog/product/_navigation.html.twig:14`
- `templates/customer/_auth_layout.html.twig:3`
- `templates/order/_order_item_supplier_card.html.twig:5`
- `templates/_footer.html.twig:1`
- `templates/home/about.html.twig:21,61`

**UX Impact:** Subtle visual inconsistency in dark mode — borders on reporting pages and the DataTable component appear slightly darker than the standardized Card/detail page borders. Most visible on pages that mix both old and new patterns (e.g., dashboard with DataTable inside a Card).

**Scope:** 40+ instances across ~18 files. Primarily concentrated in reporting templates and templates added post-Round-1.
**Importance:** Medium-High — undermines the previous standardization work.
**Effort:** Low — find-and-replace `dark:border-gray-700` → `dark:border-gray-600`.

</details>

---

### ~~B2. Text Color Tier Inconsistency (`dark:text-gray-300` Misuse)~~ ✅

**Status:** Resolved in Phase 1 PR 1a. All 67 instances across 34 files normalized: headings/card titles/table row headers → `dark:text-white` (11), breadcrumb current page → `dark:text-gray-200` (1), body text/labels/metadata/badges/buttons/legal → `dark:text-gray-400` (55). Zero `dark:text-gray-300` remains in `src/` and `templates/`.

<details><summary>Original finding</summary>

**Evidence:** The documented typography convention (`Docs/patterns/UI/Typography.md`) specifies:
- Primary text: `dark:text-white`
- Secondary text: `dark:text-gray-400`
- Tertiary/muted: `dark:text-gray-500`

However, `dark:text-gray-300` is used in 60+ instances where `dark:text-gray-400` or `dark:text-white` should apply:

**Headings using `dark:text-gray-300` (should be `dark:text-white`):**
- `templates/purchasing/purchase_order/_order_po_card.html.twig:7`
- `templates/purchasing/purchase_order/_po_card.html.twig:79`
- `templates/customer/_customer_insights_card.html.twig:5`

**Body/secondary text using `dark:text-gray-300` (should be `dark:text-gray-400`):**
- `templates/review/_review_detail_card.html.twig:24` — review body text
- `templates/review/_review_preview_card.html.twig:25` — preview text
- `templates/note/ticket/_feed_comment.html.twig:50` — comment text
- `templates/order/_order_item_supplier_card.html.twig:18` — supplier name
- `templates/catalog/category/index.html.twig:32` — meta text
- `templates/reporting/product_sales.html.twig:78` — table cells
- `templates/reporting/customer_geographic.html.twig:65` — table cells
- `templates/reporting/show.html.twig:56,118` — table cells

**Legal/static pages (should be `dark:text-gray-400`):**
- `templates/home/about.html.twig` — 13 instances
- `templates/home/privacy.html.twig` — 12 instances
- `templates/home/terms.html.twig` — 8 instances
- `templates/home/contact.html.twig` — 2 instances

**Also inconsistent:**
- `templates/components/Breadcrumb.html.twig:13` — current page uses `dark:text-gray-300` instead of `dark:text-gray-200` or `dark:text-white`

**UX Impact:** The extra-bright `gray-300` text in dark mode creates a slightly uneven reading experience — some body text appears brighter than identical-purpose text elsewhere. Most noticeable when switching between detail pages and reporting pages.

**Scope:** 60+ instances across ~25 files.
**Importance:** Medium — affects visual consistency but not functionality.
**Effort:** Medium — each instance needs evaluation (is it a heading → `dark:text-white`, or secondary text → `dark:text-gray-400`?).

</details>

---

### ~~B3. SortLink Component Missing `aria-sort` Attributes~~ ✅

**Status:** Resolved in Phase 0. SortLink now includes `aria-current="true"` on active sort links, `sr-only` text describing sort direction, and `aria-hidden="true"` on decorative icons.

<details><summary>Original finding</summary>

**Evidence:** `templates/components/SortLink.html.twig` toggles visual sort icons (caret up/down) but provides no ARIA indication of sort state.

No `aria-sort="ascending"`, `aria-sort="descending"`, or `aria-sort="none"` on the parent `<th>` element.

**UX Impact:** Screen reader users cannot determine which column is currently sorted or in which direction.
**Scope:** All 14+ index pages that use SortLink, plus 5+ reporting table pages.
**Importance:** High — core accessibility gap affecting every list view.

</details>

---

### ~~B4. Flash Message Container Missing `aria-live` Region~~ ✅

**Status:** Resolved in Phase 0. Flash container now has `aria-live="polite"` and `pointer-events-none`.

<details><summary>Original finding</summary>

**Evidence:** `templates/base.html.twig:54` — no `aria-live="polite"` or `role="status"` attribute.

**UX Impact:** Screen reader users receive no feedback after actions (create, update, delete).
**Scope:** Global — affects every flash message across the entire application.
**Importance:** High — core accessibility gap affecting action feedback.

</details>

---

### ~~B5. KpiCard Trend Indicators Not Accessible~~ ✅

**Status:** Resolved in Phase 0. KpiCard now includes `<span class="sr-only">increased</span>` / `<span class="sr-only">decreased</span>` alongside trend icons.

<details><summary>Original finding</summary>

**Evidence:** `templates/components/KpiCard.html.twig:48-76` — trend icons are purely visual with no text alternative.

**UX Impact:** Visually impaired users cannot understand whether a KPI metric increased or decreased.
**Scope:** All dashboard KPI grids (66 KpiCard usages).
**Importance:** Medium-High — dashboards are a primary workflow.

</details>

---

### ~~B6. Reporting Dashboard Uses Non-Semantic Headings~~ ✅

**Status:** Resolved in Phase 1b. Three `<div>` section headings in `templates/reporting/show.html.twig` changed to `<h2>` with identical styling.

<details><summary>Original finding</summary>

**Evidence:** `templates/reporting/show.html.twig` uses `<div class="mb-3 font-semibold text-gray-700 dark:text-gray-500">` for section titles like "Order Overview", "Sales Overview", and "Action Required" instead of semantic heading tags (`<h2>`, `<h3>`).

**UX Impact:** Screen reader users cannot navigate the dashboard by headings. The page structure is flat — no heading hierarchy exists below the `<h1>` page title.

**Scope:** Dashboard and reporting pages (~6 templates with non-semantic section headings).
**Importance:** Medium — affects navigability of the most data-dense pages.
**Effort:** Low — change `<div>` to `<h2>` with identical styling.

</details>

---

### ~~B7. Header Theme Toggle Button Missing Focus Indicator~~ ✅

**Status:** Resolved in Phase 0. Theme toggle now has `focus:ring-2 focus:ring-gray-500` matching adjacent header buttons.

<details><summary>Original finding</summary>

**Evidence:** `templates/_header.html.twig:25` — `focus:outline-hidden` with no fallback focus ring.

**UX Impact:** Keyboard users cannot see when focus is on the theme toggle.
**Scope:** Single button, but globally visible in the header.
**Importance:** Medium — affects keyboard navigation on every page.

</details>

---

### ~~B8. Modal Close Button Missing Focus-Visible Styling~~ ✅

**Status:** Resolved in Phase 0. Modal close button now has `focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500`.

<details><summary>Original finding</summary>

**Evidence:** `templates/components/Modal.html.twig:37-44` — hover states but no `focus-visible:outline`.

**UX Impact:** Keyboard users inside a modal cannot see focus on the close button.
**Scope:** All modals application-wide.
**Importance:** Medium — affects keyboard navigation in all modal flows.

</details>

---

### ~~B9. Reporting Tables Missing `<caption>` Elements~~ ✅

**Status:** Resolved in Phase 1b. Added `<caption class="sr-only">` to all 6 inline reporting tables (`product_sales`, `customer_insights`, `customer_geographic`, `customer_segments`, `overdue_orders`, `po_item_performance`). Dashboard DataTables get captions automatically via the new `caption` prop (E2/C7).

<details><summary>Original finding</summary>

**Evidence:** All reporting tables (`product_sales.html.twig`, `order_summary.html.twig`, `customer_insights.html.twig`, `overdue_orders.html.twig`, `customer_geographic.html.twig`, `customer_segments.html.twig`, `po_item_performance.html.twig`) have section headings above the table as `<p>` or `<div>` elements, but no `<caption>` inside the `<table>`.

Example from `product_sales.html.twig:35-43`:
```html
<p class="font-medium text-gray-600 dark:text-gray-500">Top Products</p>
...
<table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
    <thead>...
```

The heading "Top Products" is visually associated but not programmatically linked to the table.

**UX Impact:** Screen reader users navigating by table landmarks hear an unlabelled table. They must read the content to determine what data the table shows.

**Scope:** 7+ reporting tables.
**Importance:** Medium — accessibility gap on data-dense pages.
**Effort:** Low — add `<caption class="sr-only">` to each table or use `aria-labelledby` pointing to the heading.

</details>

---

### ~~B10. ProductImage Component Missing Width/Height Attributes~~ ✅

**Status:** Resolved in Phase 1b. Added `getImageDimensions()` method to `ProductImage.php` returning `{width, height}` per filter size (90/130/230). Template now renders `width` and `height` attributes on the `<img>` tag.

<details><summary>Original finding</summary>

**Evidence:** `templates/components/ProductImage.html.twig:12-17`:
```html
<img class="relative z-0 inset-0 object-cover ..."
     loading="lazy"
     src="{{ asset(product_uploads~this.imageName)|imagine_filter(this.filter) }}"
     alt="{{ this.alt }}">
```

No `width` or `height` attributes. When images load lazily, the browser cannot reserve space, causing Cumulative Layout Shift (CLS).

**UX Impact:** Layout shift when product images load, especially on index pages with many product cards. Degrades perceived performance.

**Scope:** All product listing and detail pages with images.
**Importance:** Medium — affects Core Web Vitals (CLS metric).
**Effort:** Low-Medium — add dimensions based on the Imagine filter sizes (small_thumbnail, medium_thumbnail, large_thumbnail).

</details>

---

## C) Systemic Improvements

### ~~C1. Complete Dark Border Standardization (Second Pass)~~ ✅

**Status:** Completed in Phase 0.

<details><summary>Original description</summary>

**What changes:** Find-and-replace `dark:border-gray-700` → `dark:border-gray-600` across all templates, matching the standard established in Round 1.

**Where:** ~18 files, primarily in `templates/reporting/`, `templates/audit/`, `templates/components/DataTable.html.twig`, `templates/_footer.html.twig`, `templates/home/about.html.twig`, `templates/customer/_auth_layout.html.twig`, `templates/catalog/product/_navigation.html.twig`, `templates/order/_order_item_supplier_card.html.twig`.

**Risk:** Very low — purely cosmetic, no behavioral change.
**Effort:** Small (1 hour).
**Value:** High — completes the standardization from Round 1 and prevents further drift.

</details>

---

### ~~C2. Normalize `dark:text-gray-300` to Documented Tiers~~ ✅

**Status:** Completed in Phase 1 PR 1a. 67 instances across 34 files normalized to correct tiers. Decision on H5 (prose tier): `dark:text-gray-400` is sufficient for long-form reading; no separate prose tier needed.

<details><summary>Original description</summary>

**What changes:** Audit all 60+ instances of `dark:text-gray-300` and reclassify to the correct tier:
- Headings/prominent text → `dark:text-white`
- Secondary body text → `dark:text-gray-400`
- Muted/tertiary text → `dark:text-gray-500`

**Where:** 25+ files across reporting, legal pages, card partials, and components.

**Risk:** Low — visual change in dark mode only. Needs manual review per instance.
**Effort:** Medium (half day — each instance needs contextual evaluation).
**Value:** Medium — aligns all dark mode text to the documented typography scale.

</details>

---

### ~~C3. Add Accessibility Attributes to Interactive Components~~ ✅

**Status:** All 7 items completed. Items 1-5 in Phase 0, items 6-7 in Phase 1b.

1. ~~**SortLink**: `aria-current`, sr-only sort direction text, `aria-hidden` on icons~~ ✅
2. ~~**Flash container**: `aria-live="polite"` + `pointer-events-none`~~ ✅
3. ~~**KpiCard**: sr-only "increased" / "decreased" text~~ ✅
4. ~~**Theme toggle**: `focus:ring-2 focus:ring-gray-500`~~ ✅
5. ~~**Modal close**: `focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500`~~ ✅
6. ~~**Reporting tables**: Add `<caption class="sr-only">`~~ ✅ (Phase 1b)
7. ~~**Reporting headings**: Change `<div>` to `<h2>`~~ ✅ (Phase 1b)

---

### ~~C4. Add Explicit Dimensions to ProductImage Component~~ ✅

**Status:** Completed in Phase 1b. Added `getImageDimensions()` method to `ProductImage.php` and `width`/`height` attributes to the `<img>` tag. Dimensions match Tailwind classes: 90×90, 130×130, 230×230.

<details><summary>Original description</summary>

**What changes:** Add `width` and `height` attributes to the `<img>` tag in `ProductImage.html.twig` based on the Imagine filter dimensions. Map filter names to pixel dimensions in `ProductImage.php`:
- `small_thumbnail` → 80×80
- `medium_thumbnail` → 200×200
- `large_thumbnail` → 400×400

**Where:** `ProductImage.html.twig`, `ProductImage.php`.

**Risk:** Very low — prevents layout shift, no visual change.
**Effort:** Small (1-2 hours).
**Value:** Medium — improves CLS score and perceived performance.

</details>

---

### C5. Standardize Hover States Across Dark Mode

**What changes:** Audit dark-mode hover states and align to documented surface hierarchy:
- Card body hover: `dark:hover:brightness-125` (current, via Card component)
- Table row hover: `dark:hover:bg-gray-700/50` (current in reporting)
- Menu item hover: `dark:hover:bg-gray-800` (current in sidebar)
- Card footer links hover: `dark:hover:bg-white/5` (current in some cards)

These are actually reasonable per-context hover states. Document the hover convention in `Docs/patterns/UI/Surfaces.md` to prevent confusion rather than force uniformity.

**Where:** `Docs/patterns/UI/Surfaces.md` (documentation update).

**Risk:** None — documentation only.
**Effort:** Small (30 minutes).
**Value:** Low-Medium — prevents future questions about "which hover pattern to use".

---

### ~~C6. Complete Focus Ring Migration~~ ✅

**Status:** Resolved in Phase 2a. Migrated all button `focus:ring-*` patterns to `focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500`: Toast close button, sidebar toggle, theme toggle, user avatar button. Form inputs (login, register, resend-verify, search) intentionally left with `focus:ring-*` to match Flowbite form theme styling. Modal close button was already migrated in Phase 0.

<details><summary>Original description</summary>

**What changes:** Several templates still use `focus:ring-*` instead of the preferred `focus-visible:outline-*` pattern:
- `templates/customer/security/login.html.twig:28,32,37` — form inputs
- `templates/components/Search.html.twig:55` — search input
- `templates/components/Toast.html.twig:17` — close button
- `templates/components/Modal.html.twig:38` — close button

Migrate all remaining `focus:ring-*` patterns on interactive elements to `focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-{color}`. Note: form field focus rings managed by the Flowbite form theme should stay as-is (they're controlled by the bundle).

**Where:** 4-5 template files with custom focus styling.

**Risk:** Low — visual change on keyboard focus only.
**Effort:** Small (1-2 hours).
**Value:** Medium — eliminates click-triggered focus rings (mouse users) while preserving keyboard indicators.

</details>

---

### ~~C7. Add `<caption>` to DataTable Component~~ ✅

**Status:** Completed in Phase 1b. Added `caption` prop to `DataTable.html.twig`. Falls back to `title` when no explicit caption is provided, so the 2 dashboard DataTable usages get accessible captions with zero template changes. Inline reporting tables also received manual `<caption class="sr-only">` additions (B9).

<details><summary>Original description</summary>

**What changes:** Add an optional `caption` prop to `DataTable.html.twig`. When provided, render `<caption class="sr-only">{{ caption }}</caption>` inside the `<table>`. This benefits all tables using the component automatically.

For reporting tables not using DataTable (inline `<table>` elements), add `<caption class="sr-only">` manually.

**Where:** `templates/components/DataTable.html.twig`, 5+ reporting templates with inline tables.

**Risk:** Very low — sr-only caption is invisible to sighted users.
**Effort:** Small (1-2 hours).
**Value:** Medium — improves table accessibility for screen reader users.

</details>

---

## D) External Pattern Insights

### D1. Tab Navigation for Detail Pages (Tailwind UI: Application UI / Navigation / Tabs)

**Pattern:** Tailwind UI tabs use `flex border-b border-gray-200` with active state `border-primary-500 text-primary-600`. Mobile fallback converts to a `<select>` dropdown.

**Where it applies:** Detail pages with multiple sections — e.g., Product show (which already has a sub-navigation via `_navigation.html.twig` with tabs for Info, Reviews, Suppliers, Pricing, Images, Stock).

**Why it helps:** The product sub-navigation already implements a tab-like pattern with `rounded-lg bg-gray-100 px-3 py-1.5` pills. However, it lacks `role="tablist"` / `role="tab"` ARIA attributes and doesn't have a mobile-friendly fallback.

**Effort:** Small — add ARIA roles to existing navigation, consider select fallback for mobile.
**Risk:** Low — enhancement of existing pattern.
**Recommendation:** **Adopt incrementally** — add ARIA tab semantics to product sub-navigation. Don't introduce tabs on other detail pages unless they naturally have multi-section content. Avoid creating tabs just for the sake of having them.

---

### D2. Command Palette / Quick Search (Tailwind UI: Navigation / Command Palettes)

**Pattern:** `Cmd+K` overlay with search input, filtered results, keyboard navigation. `fixed inset-0 z-50` with centered search and filtered results.

**Where it applies:** Quick navigation across bounded contexts — jumping to specific orders, products, customers by name/ID.

**Current state:** Navigation requires opening the sidebar drawer and clicking through sections.

**Effort:** High — requires global search endpoint, Stimulus controller, result ranking.
**Risk:** Medium — significant feature addition.
**Recommendation:** **Defer** — valuable for power users navigating many entities, but significant engineering effort. The sidebar with active state + badges + collapsible sections serves current needs. Revisit only if users report navigation friction.

---

### D3. Ring-Based Card Borders (Tailwind UI / Catalyst: Cards)

**Pattern:** Modern Tailwind UI prefers `ring-1 ring-gray-900/5` (or `ring-1 ring-black/5`) over `border border-gray-200` for card borders. `ring` uses `box-shadow` and doesn't affect layout, while `border` adds to the box model.

**Where it applies:** Card component (`templates/components/Card.html.twig`).

**Current state:** Cards use `border border-gray-200 dark:border-gray-600` (via `Card.php`). This works well and is consistent.

**Effort:** Small — change border classes in `Card.php`.
**Risk:** Low-Medium — visual change across all 139 Card usages; needs visual review.
**Recommendation:** **Skip** — the current `border` approach works well and is well-established. The `ring` approach is a modern convention but provides no material improvement over the existing pattern. Switching would create churn without clear benefit.

---

### D4. Anchor-Positioned Dropdowns (Catalyst / Headless UI v2.0)

**Pattern:** Dropdowns and popovers use Floating UI for automatic anchor positioning instead of fixed position offsets.

**Where it applies:** The sidebar's Flowbite `data-collapse-toggle` dropdowns and the user menu dropdown.

**Current state:** Dropdowns use Flowbite's built-in positioning. The user menu uses a simple `hidden` toggle via Stimulus.

**Recommendation:** **Skip** — Flowbite handles positioning adequately. Switching to Floating UI would require replacing the Flowbite dependency and rewriting dropdown behavior. Not worth the disruption.

---

### D5. CSS-First Theme Tokens (Tailwind CSS v4 `@theme`)

**Pattern:** Tailwind v4 moves configuration to CSS via `@theme` directive. All design tokens become native CSS custom properties, inspectable in DevTools and runtime-overridable.

**Where it applies:** The project already uses `@theme` in `assets/styles/app.css:11-99` for custom colors (primary, supplier1-4, danger semantic tokens) and animations. This is already well-adopted.

**Current state:** Custom color tokens are defined correctly. The `@custom-variant dark` declaration (`app.css:7`) properly implements class-based dark mode.

**Recommendation:** **Already adopted** — no changes needed. The project is already using v4's `@theme` system effectively. One potential enhancement: define semantic border/surface tokens (e.g., `--color-divider`, `--color-surface-*`) to prevent the border-color drift issues found in B1. However, this adds abstraction without enough benefit given the small codebase.

---

### D6. Stacked List with Sticky Column Header (Tailwind UI: Lists / Stacked Lists)

**Pattern:** Tailwind UI's sticky list headers use `sticky top-0 z-10 bg-gray-50/75 backdrop-blur-sm` for column headers that stay visible during scroll.

**Where it applies:** The Search component's sort header already uses `sticky top-0 z-10` positioning (implemented in Round 1).

**Current state:** Already implemented. The sort header sticks on scroll with `bg-gray-100 dark:bg-gray-800`.

**Recommendation:** **Already adopted** — consider adding `backdrop-blur-sm` for a subtle glass effect, but this is purely cosmetic polish.

---

### D7. Toast Stacking and Positioning (Tailwind UI: Overlays / Notifications)

**Pattern:** Tailwind UI notification panels stack multiple toasts vertically with enter/leave animations. The container uses `pointer-events-none` on the wrapper and `pointer-events-auto` on individual toasts.

**Where it applies:** Flash message container (`base.html.twig:54`).

**Current state:** Toasts use `fixed top-5 right-5 z-50 flex flex-col gap-3`. Multiple simultaneous toasts will stack correctly via `flex-col gap-3`. The `closeable_controller.js` handles auto-dismiss with animated fade-out via `stimulus-use` transitions.

**Observation from Round 2 (H3):** Multiple rapid toasts will stack correctly, but the container lacks `pointer-events-none` — this means the toast container captures clicks even in the gap area between toasts. Adding `pointer-events-none` to the container and `pointer-events-auto` to each Toast would fix this.

**Effort:** Very small.
**Risk:** Very low.
**Recommendation:** ~~**Adopt** — add `pointer-events-none` to the flash container, `pointer-events-auto` to Toast. Also add `aria-live="polite"` (see B4).~~ ✅ Done in Phase 0.

---

### D8. `prefers-reduced-motion` for AOS Controller

**Pattern:** Modern Tailwind/CSS patterns respect `prefers-reduced-motion: reduce` for all animations.

**Where it applies:** `assets/controllers/aos_controller.js` initializes AOS with `{once: true}` but does not check `prefers-reduced-motion`. The main CSS (`app.css:162-175`) correctly respects reduced motion for view transitions, but the AOS library runs independently.

**Current state:** Users who prefer reduced motion will still see AOS scroll animations.

**Effort:** Very small — check `window.matchMedia('(prefers-reduced-motion: reduce)')` before initializing AOS.
**Risk:** Very low.
**Recommendation:** ~~**Adopt** — add reduced-motion check to AOS controller.~~ ✅ Done in Phase 0.

---

## E) Component Enhancement Opportunities

### ~~E1. SortLink: `aria-sort` Support~~ ✅

**Status:** Implemented in Phase 0 via `aria-current="true"`, sr-only direction text, and `aria-hidden` on decorative icons. Chose sr-only text approach over `aria-sort` on `<th>` since SortLink renders as an `<a>` inside a `<th>` and cannot control the parent element.

---

### ~~E2. DataTable: Optional `<caption>` Prop~~ ✅

**Status:** Implemented in Phase 1b. Added `caption` prop with fallback to `title`. Both dashboard DataTable usages now have accessible captions automatically.

<details><summary>Original description</summary>

**Candidate:** Add a `caption` prop to `DataTable.html.twig`.

**Evidence:** 2 dashboard tables use DataTable; 7+ reporting tables use inline `<table>`. None have `<caption>`.

**Why a component improvement helps:** Adding a caption prop to DataTable gives both existing usages an accessible label with zero effort. For inline reporting tables, a `<caption class="sr-only">` should be added manually.

**Decision:** **Implement** — add `{% props title, caption=null %}` and render `{% if caption %}<caption class="sr-only">{{ caption }}</caption>{% endif %}` inside the `<table>`.

</details>

---

### ~~E3. KpiCard: Accessible Trend Description~~ ✅

**Status:** Implemented in Phase 0. Added `<span class="sr-only">increased</span>` and `<span class="sr-only">decreased</span>` alongside trend icons.

---

### E4. FinancialSummary Partial — Deferred

**Decision:** **Still deferred** from Round 2. Only 2 instances (`order/show.html.twig`, `_po_card.html.twig`). Extract if a third appears.

---

## F) Quick Wins (1-3 Days)

### ~~F1. Add `aria-live="polite"` to Flash Container~~ ✅
### ~~F2. Fix Theme Toggle Focus Indicator~~ ✅
### ~~F3. Fix Modal Close Button Focus Indicator~~ ✅
### ~~F4. Add SortLink Accessibility~~ ✅
### ~~F5. Add KpiCard Screen-Reader Trend Text~~ ✅
### ~~F6. Add `prefers-reduced-motion` Guard to AOS Controller~~ ✅
### ~~F7. Standardize Dark Borders (Second Pass)~~ ✅

All quick wins completed in Phase 0.

---

## G) Phased Improvement Plan (PR-Sized)

### ~~Phase 0 — Accessibility & Standards Polish~~ ✅

**Status:** All 7 items completed.

<details><summary>Original scope</summary>

**Changes:**
1. Add `aria-live="polite"` + `pointer-events-none` to flash container (F1) ✅
2. Fix theme toggle focus indicator (F2) ✅
3. Fix modal close button focus indicator (F3) ✅
4. Add SortLink accessibility — sr-only sort direction text + `aria-current` (F4) ✅
5. Add KpiCard screen-reader trend text (F5) ✅
6. Add `prefers-reduced-motion` guard to AOS controller (F6) ✅
7. Standardize `dark:border-gray-700` → `dark:border-gray-600` (F7) ✅

</details>

---

### Phase 1 — Text Color & Component Normalization (1-2 PRs)

**~~PR 1a: Normalize `dark:text-gray-300` → Documented Tiers~~** ✅
- 67 instances across 34 files normalized: 11 → `dark:text-white`, 1 → `dark:text-gray-200`, 55 → `dark:text-gray-400`
- Zero `dark:text-gray-300` remains in code

**~~PR 1b: Component Accessibility Enhancements~~** ✅
- `<caption>` prop added to DataTable component with `title` fallback (E2/C7)
- `<caption class="sr-only">` added to 6 inline reporting tables (B9)
- Dashboard section `<div>` headings converted to `<h2>` (B6)
- `width`/`height` attributes added to ProductImage component (B10/C4)
- Toast `pointer-events-auto` already present — no change needed
- Files changed: `DataTable.html.twig`, `ProductImage.html.twig`, `ProductImage.php`, `reporting/show.html.twig`, 6 reporting templates

**Tests to add/update:**
- Visual regression: dark mode screenshots of reporting pages before/after text color changes
- DataTable: add assertion for `<caption>` element in dashboard controller test
- ProductImage: verify `width`/`height` attributes in a product show flow test

**Validation approach:**
- Side-by-side dark mode comparison for text color changes
- Screen reader walkthrough of reporting dashboard (headings, tables, KPIs)
- CLS measurement before/after ProductImage dimensions

**Stop conditions:**
- If `dark:text-gray-400` makes legal page text too dim, use `dark:text-gray-300` for long-form prose and document as an intentional exception
- If ProductImage dimensions cause aspect ratio issues with non-square thumbnails, use `aspect-ratio` CSS instead of fixed dimensions

---

### Phase 2 — Optional Refinements (Opportunistic)

**~~PR 2a: Focus Ring Migration~~** ✅
- Migrated 4 buttons to `focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500`: Toast close (`Toast.html.twig`), sidebar toggle, theme toggle, user avatar (`_header.html.twig`)
- Form inputs (login, register, resend-verify, search) left as-is — they use standard Flowbite `focus:ring` pattern and changing them would create inconsistency with Flowbite-themed forms elsewhere

**PR 2b: Product Sub-Navigation Tabs Accessibility**
- Add `role="tablist"` / `role="tab"` / `aria-selected` to product sub-navigation (`_navigation.html.twig`)
- Consider mobile-friendly fallback (select dropdown or horizontal scroll)

**PR 2c: Hover State Documentation**
- Document hover conventions in `Docs/patterns/UI/Surfaces.md`
- Table row hover: `dark:hover:bg-gray-700/50`
- Menu item hover: `dark:hover:bg-gray-800`
- Card footer link hover: `dark:hover:bg-white/5`
- Card body hover (via showLink): `dark:hover:brightness-125`

**Tests to add/update:**
- Focus ring: manual keyboard test, no automated test needed
- Tab semantics: add ARIA attribute assertions to product show flow test

**Validation approach:**
- Keyboard navigation test for focus ring changes
- Screen reader test for tab navigation
- Review hover documentation with design system principles

**Stop conditions:**
- Focus ring migration: Stop if Flowbite form theme overrides create conflicts. Document exceptions.
- Tab semantics: Stop if product sub-navigation is rarely used on mobile (check analytics first).

---

## H) Questions & Observations

No blocking questions. All proposed changes can proceed independently.

**Non-blocking observations:**

1. **Tailwind v4 `@theme` semantic tokens** — The project could define `--color-divider` and `--color-surface-*` tokens to prevent border-color drift (like B1). However, the 40 instances of `dark:border-gray-700` are all easily found via grep, so a CSS token adds indirection without proportionate value in a codebase this size. Prefer the direct find-and-replace approach.

2. **Toast stacking under rapid actions** — The `flex-col gap-3` layout stacks toasts correctly, but rapid batch operations (if added later) could create a tall stack. The existing `closeable_controller.js` auto-dismiss (3500ms/6000ms) prevents buildup under normal use. No change needed unless batch operations are added.

3. **Sortable drag-and-drop accessibility** — `sortable_controller.js` uses SortableJS which has no built-in keyboard support. If reorder functionality is important for keyboard users, consider adding keyboard-based reorder (up/down arrows) as an alternative to drag-and-drop. Currently used for product image ordering only — low priority.

4. **Container queries** — Tailwind CSS v4 supports `@container` queries which could benefit the Turbo Frame architecture (content width varies depending on sidebar state). Not needed currently — viewport breakpoints work well — but worth considering if sidebar becomes a persistent panel rather than a drawer.

5. **~~`dark:text-gray-300` as intentional prose tier~~** — Resolved: `dark:text-gray-400` provides sufficient contrast for long-form reading in dark mode. No separate prose tier needed. All legal/about pages normalized to `dark:text-gray-400`.
