import { Controller } from '@hotwired/stimulus';

/**
 * Applies a one-shot glow animation to elements created within a recent
 * time window. Designed for list pages where new items should stand out
 * momentarily after creation.
 *
 * Usage:
 *   <div data-controller="new-item-highlight"
 *        data-new-item-highlight-created-at-value="2026-04-01T12:00:00+00:00"
 *        data-new-item-highlight-threshold-value="3000">
 *
 * Values:
 *   createdAt  — ISO 8601 timestamp of the item's creation
 *   threshold  — Age in milliseconds below which the highlight triggers (default: 3000)
 */
export default class extends Controller {
    static values = {
        createdAt: String,
        threshold: { type: Number, default: 3000 },
    };

    connect() {
        if (!this.createdAtValue) return;

        const age = Date.now() - new Date(this.createdAtValue).getTime();

        if (age >= 0 && age < this.thresholdValue) {
            this.element.classList.add('new-item-highlight');

            this.element.addEventListener('animationend', () => {
                this.element.classList.remove('new-item-highlight');
            }, { once: true });
        }
    }
}
