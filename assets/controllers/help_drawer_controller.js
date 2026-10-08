import { Controller } from '@hotwired/stimulus';

/**
 * Contextual help for the kit Sheet in _help_drawer.html.twig. The kit's dialog controller on the
 * same element opens and closes the panel; this adds the `?` shortcut and loads the current
 * page's help into the Turbo Frame.
 */
export default class extends Controller {
    static targets = ['frame'];

    get dialog() {
        return this.application.getControllerForElementAndIdentifier(this.element, 'dialog');
    }

    get isOpen() {
        return this.dialog.dialogTarget.open;
    }

    open() {
        const helpUrl = '/help?page=' + encodeURIComponent(window.location.pathname);
        const frame = this.frameTarget;

        // Only update src when the page has changed. The frame uses loading="lazy", so the
        // fetch waits until the panel is shown. Compare pathname+search since frame.src
        // resolves to an absolute URL.
        if (!frame.src || new URL(frame.src).pathname + new URL(frame.src).search !== helpUrl) {
            frame.src = helpUrl;
        }

        this.dialog.open();
    }

    close() {
        this.dialog.close();
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    keydown(event) {
        const tag = event.target.tagName;
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag) || event.target.isContentEditable) return;
        if (event.key === '?' || (event.shiftKey && event.key === '/')) {
            event.preventDefault();
            this.toggle();
        }
    }
}
