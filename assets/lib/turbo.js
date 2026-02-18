/**
 * Trigger a Turbo page refresh (replaces current page content without a full reload).
 */
export function turboRefresh() {
    Turbo.visit(window.location, { action: 'replace' });
}
