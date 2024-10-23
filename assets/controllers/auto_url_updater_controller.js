import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String
    }

    connect() {
        const newUrl = encodeURI(this.urlValue);

        if (newUrl) {
            history.replaceState(history.state, '', newUrl); // Update the URL without navigation
        }
    }
}