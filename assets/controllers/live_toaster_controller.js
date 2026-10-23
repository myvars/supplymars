import { Controller } from '@hotwired/stimulus';

/**
 * On a wrapper around the page's toaster (base.html.twig). A live refresh's morph keeps the toaster as it is: a live
 * toast is not in the server's HTML, and Sonner has rearranged the rest. Any new flash the refreshed page brings is
 * appended, and Sonner shows it as it does a toast from a Turbo Stream.
 * See Docs/patterns/Turbo/README.md#live-page-updates.
 */
export default class extends Controller {
    keep(event) {
        if (event.target !== this.element) {
            return;
        }

        event.preventDefault();

        const toaster = this.element.querySelector('#flash-container');
        event.detail.newElement?.querySelectorAll('[data-slot="toast"]').forEach((toast) => {
            toaster?.append(document.importNode(toast, true));
        });
    }
}
