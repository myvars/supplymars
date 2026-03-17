import { Controller } from '@hotwired/stimulus';
import { visit } from '@hotwired/turbo';

/**
 * Makes an entire card clickable as a link, while allowing inner
 * links and buttons to work normally.
 *
 * Usage:
 *   <div data-controller="card-link"
 *        data-card-link-url-value="/purchase/order/123"
 *        data-action="click->card-link#visit">
 */
export default class extends Controller {
    static values = { url: String };

    visit(event) {
        if (event.target.closest('a, button')) return;
        if (event.defaultPrevented) return;

        visit(this.urlValue);
    }
}
