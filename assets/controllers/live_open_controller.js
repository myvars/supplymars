import { Controller } from '@hotwired/stimulus';

/**
 * On the root of a kit component that opens (Sheet, DropdownMenu, Dialog, AlertDialog), set where the component is used,
 * so the kit's files stay as installed. While the component is open, a live refresh's morph leaves it alone, with what
 * was loaded into it (Docs/patterns/Turbo/README.md#live-page-updates). The attribute is never in the server's HTML,
 * and the root has no id, so other renders still replace it; once closed, the next refresh morphs it as usual. The
 * morph pairs the kept root with the server's copy by the ids inside it (a Popover has none).
 * The kit's controllers dispatch no open or close events, so this watches the attributes they set.
 */
export default class extends Controller {
    connect() {
        this.observer = new MutationObserver(() => this.#update());
        this.observer.observe(this.element, { attributes: true, attributeFilter: ['open', 'data-state'], subtree: true });
        this.#update();
    }

    disconnect() {
        this.observer.disconnect();
        this.element.removeAttribute('data-turbo-permanent');
    }

    // A Sheet or Dialog opens its <dialog>; a DropdownMenu sets data-state on its root
    #update() {
        const open = this.element.dataset.state === 'open' || this.element.querySelector('dialog[open]') !== null;
        this.element.toggleAttribute('data-turbo-permanent', open);
    }
}
