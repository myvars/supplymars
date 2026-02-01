import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
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
        const yAxisType = yScaleOptions?.axisType || false; // Check Y axis data type

        // Configure the chart based on the formatting type
        this.configureYAxis(config, yAxisType);
        this.configureTooltip(config, yAxisType);
    };

    onConnect = (event) => {
        // Placeholder for actions to take once the chart is fully connected
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