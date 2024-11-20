import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('chartjs:pre-connect', this.onPreConnect);
        this.element.addEventListener('chartjs:connect', this.onConnect);
    }

    disconnect() {
        this.element.removeEventListener('chartjs:pre-connect', this.onPreConnect);
        this.element.removeEventListener('chartjs:connect', this.onConnect);
    }

    onPreConnect = (event) => {
        const config = event.detail.config;
        const isCurrency = config?.options?.scales?.y?.grid?.currency || false;

        if (isCurrency) {
            this.configureYAxis(config);
            this.configureTooltip(config);
        }
    };

    onConnect = (event) => {
        //    console.info('Chart connected:', event.detail.chart);
    };

    configureYAxis(config) {
        config.options.scales.y.ticks = {
            callback: (value) => {
                const currency = '£';
                if (value >= 1_000_000) {
                    return `${currency}${(value / 1_000_000).toFixed(1)}M`;
                } else if (value >= 1_000) {
                    return `${currency}${(value / 1_000).toFixed(0)}k`;
                }
                return `${currency}${value.toLocaleString()}`;
            },
        };
    }

    configureTooltip(config) {
        config.options.plugins = config.options.plugins || {};
        config.options.plugins.tooltip = {
            callbacks: {
                label: (tooltipItem) => {
                    const currency = '£';
                    const value = tooltipItem.raw;
                    if (value >= 1_000_000) {
                        return `${currency}${(value / 1_000_000).toFixed(1)}M`;
                    } else if (value >= 1_000) {
                        return `${currency}${(value / 1_000).toFixed(0)}k`;
                    }
                    return `${currency}${Math.round(value).toLocaleString()}`;
                },
            },
        };
    }
}