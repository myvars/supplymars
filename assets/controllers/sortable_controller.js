import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';
import { turboRefresh } from '../lib/turbo.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        reorderUrl: String,
    };

    connect() {
        const sortable = new Sortable(this.element, {
            animation: 150,
            onEnd: async () => {
                if (!this.reorderUrlValue) return;

                try {
                    const response = await fetch(this.reorderUrlValue, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(sortable.toArray()),
                    });
                    if (!response.ok) {
                        console.error('Network response was not ok.');
                    }
                } catch (error) {
                    console.error(`Something went wrong! ${error.message}`);
                }
                turboRefresh();
            },
        });
    }
}
