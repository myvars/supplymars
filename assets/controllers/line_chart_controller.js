import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static currencySymbol = '£';

    connect() {
        this.element.addEventListener('chartjs:pre-connect', this.onPreConnect);
    }

    disconnect() {
        this.element.removeEventListener('chartjs:pre-connect', this.onPreConnect);
    }

    onPreConnect = (event) => {
        const config = event.detail?.config;

        const yScaleOptions = config?.options?.scales?.y?.grid;
        const yAxisType = yScaleOptions?.axisType || false;

        this.configureYAxis(config, yAxisType);
        this.configureTooltip(config, yAxisType);
    };

    configureYAxis(config, yAxisType) {
        config.options.scales.y.ticks = {
            ...config.options.scales.y.ticks,
            callback: (value) => this.formatAxisValue(value, yAxisType),
        };
    }

    configureTooltip(config, yAxisType) {
        config.options.plugins = config.options.plugins || {};
        config.options.plugins.tooltip = {
            ...config.options.plugins.tooltip,
            callbacks: {
                label: (tooltipItem) => {
                    const label = tooltipItem.dataset.label || '';
                    const value = tooltipItem.raw;
                    const formatted = this.formatTooltipValue(value, yAxisType);

                    return ` ${label}: ${formatted}`;
                },
            },
        };
    }

    formatAxisValue(value, yAxisType) {
        const numericValue = Number(value);
        if (isNaN(numericValue)) {
            return 'N/A';
        }

        if (yAxisType === 'currency') {
            const currency = this.constructor.currencySymbol;
            if (numericValue >= 1000) {
                return `${currency}${(numericValue / 1000).toFixed(1)}k`;
            }
            return `${currency}${Math.round(numericValue)}`;
        }

        if (yAxisType === 'integer') {
            return Math.round(numericValue).toLocaleString();
        }

        return numericValue;
    }

    formatTooltipValue(value, yAxisType) {
        const numericValue = Number(value);
        if (isNaN(numericValue)) {
            return 'N/A';
        }

        if (yAxisType === 'currency') {
            return `${this.constructor.currencySymbol}${numericValue.toFixed(2)}`;
        }

        if (yAxisType === 'integer') {
            return Math.round(numericValue).toLocaleString();
        }

        return numericValue;
    }
}
