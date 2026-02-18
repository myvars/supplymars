const CURRENCY_SYMBOL = '£';

/**
 * Format a value for display on a chart axis (abbreviated for readability).
 */
export function formatForAxis(value, type) {
    const n = Number(value);
    if (isNaN(n)) return 'N/A';

    if (type === 'currency') {
        if (n >= 1_000_000) return `${CURRENCY_SYMBOL}${(n / 1_000_000).toFixed(1)}M`;
        if (n >= 1_000) return `${CURRENCY_SYMBOL}${(n / 1_000).toFixed(1)}k`;
        return `${CURRENCY_SYMBOL}${Math.round(n)}`;
    }

    if (type === 'percentage') return `${n.toFixed(2)}%`;
    if (type === 'integer') return Math.round(n).toLocaleString();

    return n;
}

/**
 * Format a value for display in a chart tooltip (full precision).
 *
 * @param {number} value
 * @param {string|false} type  - 'currency', 'percentage', 'integer', or false
 * @param {object} options
 * @param {boolean} options.precise - true for 2-decimal currency (line charts), false for rounded (bar/doughnut)
 */
export function formatForTooltip(value, type, { precise = false } = {}) {
    const n = Number(value);
    if (isNaN(n)) return 'N/A';

    if (type === 'currency') {
        if (precise) return `${CURRENCY_SYMBOL}${n.toFixed(2)}`;
        return `${CURRENCY_SYMBOL}${Math.round(n).toLocaleString()}`;
    }

    if (type === 'percentage') return `${n.toFixed(2)}%`;
    if (type === 'integer') return Math.round(n).toLocaleString();

    return `${n}`;
}
