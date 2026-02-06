import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static currencySymbol = '£';
    static values = {
        linkUrl: String, // URL with {label} placeholder
    };

    connect() {
        this.element.addEventListener('chartjs:pre-connect', this.onPreConnect);
        this.element.addEventListener('chartjs:connect', this.onConnect);
    }

    disconnect() {
        this.element.removeEventListener('chartjs:pre-connect', this.onPreConnect);
        this.element.removeEventListener('chartjs:connect', this.onConnect);
        if (this.chart?.canvas) {
            this.chart.canvas.removeEventListener('click', this.onClick);
        }
    }

    onPreConnect = (event) => {
        const config = event.detail?.config;

        const yScaleOptions = config?.options?.scales?.y?.grid;
        const yAxisType = yScaleOptions?.axisType || false;

        this.configureYAxis(config, yAxisType);
        this.configureTooltip(config, yAxisType);
    };

    onConnect = (event) => {
        this.chart = event.detail.chart;
        if (this.hasLinkUrlValue && this.chart?.canvas) {
            this.chart.canvas.addEventListener('click', this.onClick);
        }
    };

    onClick = (event) => {
        if (!this.chart) return;

        const elements = this.chart.getElementsAtEventForMode(
            event,
            'nearest',
            { intersect: true },
            false
        );

        if (elements.length > 0) {
            const index = elements[0].index;
            const params = this.chart.options.linkParams?.[index] ?? '';
            Turbo.visit(this.linkUrlValue + (params ? '?' + params : ''));
        }
    };

    // Configures the Y-axis ticks based on the specified type (currency or percent)
    configureYAxis(config, yAxisType) {
        config.options.scales.y.ticks = {
            callback: (value) => this.formatValue(value, yAxisType, false),  // Axis formatting
        };
    }

    // Configures the tooltip labels based on the specified type (currency or percent)
    configureTooltip(config, yAxisType) {
        config.options.plugins = config.options.plugins || {}; // Ensure the plugins object exists
        config.options.plugins.tooltip = {
            callbacks: {
                label: (tooltipItem) => {
                    const value = tooltipItem.raw;
                    return this.formatValue(value, yAxisType, true); // Tooltip formatting
                },
            },
        };
    }

    formatValue(value, yAxisType, isTooltip) {
        const numericValue = Number(value); // Ensure value is treated as a number
        if (isNaN(numericValue)) {
            return 'N/A'; // Handle invalid values
        }

        if (yAxisType === 'currency') {
            const currency = this.constructor.currencySymbol;

            if (isTooltip) {
                // Tooltip: Round to the nearest pound and add the currency symbol
                return `${currency}${Math.round(numericValue).toLocaleString()}`;
            }

            // Axis: Apply formatting for large values
            if (numericValue >= 1_000_000) {
                return `${currency}${(numericValue / 1_000_000).toFixed(1)}M`;
            } else if (numericValue >= 1_000) {
                return `${currency}${(numericValue / 1_000).toFixed(0)}k`;
            }
            return `${currency}${Math.round(numericValue).toLocaleString()}`;
        }

        if (yAxisType === 'percentage') {
            // Percentage formatting for both tooltip and axis
            return `${numericValue.toFixed(2)}%`; // Format with two decimal places
        }

        // Default fallback for unhandled types
        return numericValue;
    }
}