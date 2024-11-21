import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static currencySymbol = '£'; // Static property to define the default currency symbol

    connect() {
        // Add event listeners for custom Chart.js events
        this.element.addEventListener('chartjs:pre-connect', this.onPreConnect);
        this.element.addEventListener('chartjs:connect', this.onConnect);
    }

    disconnect() {
        // Remove the Chart.js event listeners to avoid memory leaks
        this.element.removeEventListener('chartjs:pre-connect', this.onPreConnect);
        this.element.removeEventListener('chartjs:connect', this.onConnect);
    }

    onPreConnect = (event) => {
        const config = event.detail?.config;

        const yScaleOptions = config?.options?.scales?.y?.grid;
        const isCurrency = yScaleOptions?.currency || false; // Check if currency formatting is enabled
        const isPercent = yScaleOptions?.percent || false; // Check if percentage formatting is enabled

        // Configure the chart based on the formatting type
        if (isCurrency) {
            this.configureYAxis(config, 'currency');
            this.configureTooltip(config, 'currency');
        } else if (isPercent) {
            this.configureYAxis(config, 'percent');
            this.configureTooltip(config, 'percent');
        }
    };

    onConnect = (event) => {
        // Placeholder for actions to take once the chart is fully connected
    };

    // Configures the Y-axis ticks based on the specified type (currency or percent)
    configureYAxis(config, type) {
        config.options.scales.y.ticks = {
            callback: (value) => this.formatValue(value, type, false),  // Axis formatting
        };
    }

    // Configures the tooltip labels based on the specified type (currency or percent)
    configureTooltip(config, type) {
        config.options.plugins = config.options.plugins || {}; // Ensure the plugins object exists
        config.options.plugins.tooltip = {
            callbacks: {
                label: (tooltipItem) => {
                    const value = tooltipItem.raw;
                    return this.formatValue(value, type, true); // Tooltip formatting
                },
            },
        };
    }

    formatValue(value, type, isTooltip) {
        const numericValue = Number(value); // Ensure value is treated as a number
        if (isNaN(numericValue)) {
            return 'N/A'; // Handle invalid values
        }

        if (type === 'currency') {
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

        if (type === 'percent') {
            // Percentage formatting for both tooltip and axis
            return `${numericValue.toFixed(2)}%`; // Format with two decimal places
        }

        // Default fallback for unhandled types
        return numericValue;
    }
}