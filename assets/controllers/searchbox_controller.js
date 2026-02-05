import {Controller} from "@hotwired/stimulus"
import debounce from 'debounce'

export default class extends Controller {
    static values = {
        basePath: String,
        query: String
    }
    static targets = ["queryInput"];

    initialize() {
        this.debouncedQueryInputChanged = debounce(this.queryInputChanged.bind(this), 250);
    }

    connect() {
        this.handleFocus();
    }

    disconnect() {
        this.debouncedQueryInputChanged.clear();

        if (this._focusRaf) {
            cancelAnimationFrame(this._focusRaf);
        }
    }

    queryInputChanged() {
        const url = this.buildActionURL();
        const frameId = this.element.dataset.turboFrame;
        const frame = document.getElementById(frameId);

        if (frame) {
            frame.src = url;
            history.replaceState(history.state, '', url);

            return;
        }

        this.element.action = url;
        this.element.requestSubmit();
    }

    buildActionURL() {
        const queryParams = new URLSearchParams(window.location.search);
        queryParams.set('query', this.queryInputTarget.value);

        return `${this.basePathValue}?${queryParams.toString()}`;
    }

    handleFocus() {
        this._focusRaf = requestAnimationFrame(() => {
            if (document.activeElement === this.queryInputTarget) {
                return;
            }

            const value = this.queryInputTarget.value;
            this.queryInputTarget.focus();
            this.queryInputTarget.setSelectionRange(value.length, value.length);
        });
    }
}
