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
            callback: (value) => this.formatValue(value, type), // Format the tick values
        };
    }

    // Configures the tooltip labels based on the specified type (currency or percent)
    configureTooltip(config, type) {
        config.options.plugins = config.options.plugins || {}; // Ensure the plugins object exists
        config.options.plugins.tooltip = {
            callbacks: {
                label: (tooltipItem) => {
                    const value = tooltipItem.raw;
                    return this.formatValue(value, type);
                },
            },
        };
    }

    // Formats a value as currency or percentage based on the specified type
    formatValue(value, type) {
        const numericValue = Number(value); // Ensure the value is treated as a number
        if (isNaN(numericValue)) {
            return 'N/A'; // Return 'N/A' if the value is invalid
        }

        // Handle currency formatting
        if (type === 'currency') {
            const currency = this.constructor.currencySymbol;
            if (numericValue >= 1_000_000) {
                return `${currency}${(numericValue / 1_000_000).toFixed(1)}M`; // Format millions
            } else if (numericValue >= 1_000) {
                return `${currency}${(numericValue / 1_000).toFixed(0)}k`; // Format thousands
            }
            return `${currency}${Math.round(numericValue).toLocaleString()}`; // Format smaller values
        }

        // Handle percentage formatting
        if (type === 'percent') {
            return `${numericValue.toFixed(2)}%`; // Format with two decimal places
        }

        // Return the numeric value as a fallback (e.g., for unformatted data)
        return numericValue;
    }
}