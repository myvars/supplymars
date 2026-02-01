import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        reorderUrl: String,
    };
    connect() {
        const self = this;
        var sortable = new Sortable(this.element, {
            animation: 150,
            async onEnd() {
                if (!self.reorderUrlValue) {
                    return;
                }
                try {
                    const response = await fetch(
                        self.reorderUrlValue, {
                            method: 'POST',
                            body: JSON.stringify(sortable.toArray())
                        });
                    console.log('response', response);
                    if (!response.ok) {
                        console.error('Network response was not ok.');
                    }
                } catch (error) {
                    console.error(`Something went wrong! ${error.message}`);
                }
                self.turboRefresh();
            },
        });
    }

    turboRefresh() {
        if (window.Turbo) {
            Turbo.visit(window.location, {action: 'replace'});
        }
    }
}