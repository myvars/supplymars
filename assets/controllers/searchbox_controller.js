// assets/controllers/searchbox-controller.js
import {Controller} from "@hotwired/stimulus"
import debounce from 'debounce'

export default class extends Controller {
    static values = {
        basePath: String,
        query: String
    }
    static targets = ["queryInput"];

    initialize() {
        // Create a debounced version of the queryInputChanged method
        this.debouncedQueryInputChanged = debounce(this.queryInputChanged.bind(this), 200);
    }

    connect() {
        this.handleFocus();
    }

    debouncedQueryInputChanged(event) {
        this.queryInputChanged();
    }
    queryInputChanged() {

        if (window.Turbo) {
            Turbo.visit(this.buildActionURL(), { action: "replace" });

            return;
        }

        this.element.action = this.buildActionURL();
        this.element.requestSubmit();
    }

    buildActionURL() {
        const queryParams = new URLSearchParams(window.location.search);
        queryParams.set('query', this.queryInputTarget.value);

        return `${this.basePathValue}?${queryParams.toString()}`;
    }

    handleFocus() {
        // Focus on the query input when the page loads
        setTimeout(() => {
            const value = this.queryInputTarget.value;
            this.queryInputTarget.focus();
            this.queryInputTarget.setSelectionRange(value.length, value.length);
        }, 100);
    }
}