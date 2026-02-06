import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static currencySymbol = '£'; // Default currency symbol
    static values = {
        linkUrl: String, // URL with {label} placeholder for segment value
    };

    connect() {
        // Add event listeners for Chart.js events
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

        if (config?.type === 'doughnut') {
            this.configureTooltip(config);
            this.configureClickable(config);
        }
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
            const label = this.chart.data.labels[index];
            this.navigate(label);
        }
    };

    navigate(label) {
        Turbo.visit(this.linkUrlValue.replaceAll('{label}', encodeURIComponent(label)));
    };

    configureClickable(config) {
        if (!this.hasLinkUrlValue) return;

        config.options.onHover = (event, elements) => {
            event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
        };
    }

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