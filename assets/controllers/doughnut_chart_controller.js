import { Controller } from '@hotwired/stimulus';
import { formatForTooltip } from '../lib/chart_format.js';

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
        if (config?.type !== 'doughnut') return;

        const axisType = config.options?.plugins?.tooltip?.axisType || false;

        config.options.plugins = config.options.plugins || {};
        config.options.plugins.tooltip = {
            ...config.options.plugins.tooltip,
            callbacks: {
                label: (context) => formatForTooltip(context.raw, axisType),
            },
        };

        if (this.hasLinkUrlValue) {
            config.options.onHover = (event, elements) => {
                event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
            };
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
            event, 'nearest', { intersect: true }, false
        );

        if (elements.length > 0) {
            const index = elements[0].index;
            const label = this.chart.data.labels[index];
            Turbo.visit(this.linkUrlValue.replaceAll('{label}', encodeURIComponent(label)));
        }
    };
}
