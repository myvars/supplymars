import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static currencySymbol = '£'; // Default currency symbol

    connect() {
        // Add event listeners for Chart.js events
        this.element.addEventListener('chartjs:pre-connect', this.onPreConnect);
    }

    disconnect() {
        // Remove the Chart.js event listener
        this.element.removeEventListener('chartjs:pre-connect', this.onPreConnect);
    }

    onPreConnect = (event) => {
        const config = event.detail?.config;

        if (config?.type === 'doughnut') {
            this.configureTooltip(config);
        }
    };

    configureTooltip(config) {
        config.options.plugins = config.options.plugins || {};
        config.options.plugins.tooltip = config.options.plugins.tooltip || {};

        // Define the label callback for tooltips
        config.options.plugins.tooltip.callbacks = {
            label: (context) => {
                const label = context.label || '';
                const value = context.raw;
                const axisType = config.options.plugins.tooltip.axisType || false;

                return this.formatTooltipLabel(label, value, axisType);
            },
        };
    }

    formatTooltipLabel(label, value, axisType) {
        const numericValue = Number(value);
        if (isNaN(numericValue)) {
            return `${label}: N/A`;
        }

        if (axisType === 'currency') {
            const currency = this.constructor.currencySymbol;
            return `${currency}${Math.round(numericValue).toLocaleString()}`;
        }

        if (axisType === 'percentage') {
            return `${numericValue.toFixed(2)}%`;
        }

        return `${numericValue}`;
    }
}