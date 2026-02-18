import { Controller } from '@hotwired/stimulus';
import { formatForAxis, formatForTooltip } from '../lib/chart_format.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        linkUrl: String,
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
        const yAxisType = config?.options?.scales?.y?.grid?.axisType || false;

        config.options.scales.y.ticks = {
            callback: (value) => formatForAxis(value, yAxisType),
        };

        config.options.plugins = config.options.plugins || {};
        config.options.plugins.tooltip = {
            callbacks: {
                label: (tooltipItem) => formatForTooltip(tooltipItem.raw, yAxisType),
            },
        };
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
            event, 'nearest', { intersect: true }, false
        );

        if (elements.length > 0) {
            const index = elements[0].index;
            const params = this.chart.options.linkParams?.[index] ?? '';
            Turbo.visit(this.linkUrlValue + (params ? '?' + params : ''));
        }
    };
}
