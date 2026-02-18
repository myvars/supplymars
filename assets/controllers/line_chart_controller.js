import { Controller } from '@hotwired/stimulus';
import { formatForAxis, formatForTooltip } from '../lib/chart_format.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    connect() {
        this.element.addEventListener('chartjs:pre-connect', this.onPreConnect);
    }

    disconnect() {
        this.element.removeEventListener('chartjs:pre-connect', this.onPreConnect);
    }

    onPreConnect = (event) => {
        const config = event.detail?.config;
        const yAxisType = config?.options?.scales?.y?.grid?.axisType || false;

        config.options.scales.y.ticks = {
            ...config.options.scales.y.ticks,
            callback: (value) => formatForAxis(value, yAxisType),
        };

        config.options.plugins = config.options.plugins || {};
        config.options.plugins.tooltip = {
            ...config.options.plugins.tooltip,
            callbacks: {
                label: (tooltipItem) => {
                    const label = tooltipItem.dataset.label || '';
                    return ` ${label}: ${formatForTooltip(tooltipItem.raw, yAxisType, { precise: true })}`;
                },
            },
        };
    };
}
