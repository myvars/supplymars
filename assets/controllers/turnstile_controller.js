import { Controller } from '@hotwired/stimulus';

let scriptLoading = null;

function loadTurnstileApi() {
    if (scriptLoading) {
        return scriptLoading;
    }

    if (typeof window.turnstile !== 'undefined') {
        return Promise.resolve();
    }

    scriptLoading = new Promise((resolve) => {
        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.onload = () => resolve();
        document.head.appendChild(script);
    });

    return scriptLoading;
}

export default class extends Controller {
    static values = { sitekey: String };
    static targets = ['container', 'response'];

    widgetId = null;

    connect() {
        loadTurnstileApi().then(() => {
            this.widgetId = window.turnstile.render(this.containerTarget, {
                sitekey: this.sitekeyValue,
                callback: (token) => {
                    this.responseTarget.value = token;
                },
            });
        });
    }

    disconnect() {
        if (this.widgetId !== null) {
            window.turnstile.remove(this.widgetId);
            this.widgetId = null;
        }
    }
}
