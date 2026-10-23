import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dialog', 'frame'];

    mouseDownTarget = null;

    disconnect() {
        if (this.hasDialogTarget && this.dialogTarget.open) {
            this.close();
        }
    }

    open() {
        this.dialogTarget.showModal();
        document.body.classList.add('overflow-hidden');
        // A live refresh's morph leaves the open modal alone. Not in the server's HTML, so other renders replace it
        this.dialogTarget.setAttribute('data-turbo-permanent', '');
    }

    close() {
        if (this.hasDialogTarget) {
            this.dialogTarget.close();
            this.dialogTarget.dataset.modalSize = 'md';
            this.dialogTarget.removeAttribute('data-turbo-permanent');
        }
        if (this.hasFrameTarget) {
            this.frameTarget.removeAttribute('src');
            this.frameTarget.innerHTML = '';
        }
        document.body.classList.remove('overflow-hidden');
    }

    // A live refresh morphs <body>: keep the scroll lock while the modal is open
    keepScrollLock(event) {
        if (event.target === document.body && event.detail.attributeName === 'class' && this.dialogTarget.open) {
            event.preventDefault();
        }
    }

    frameLoaded(event) {
        if (event.target === this.frameTarget && !this.dialogTarget.open) {
            this.#applySizeFromContent();
            this.open();
        }
    }

    submitEnd(event) {
        if (event.detail.success) {
            this.close();
        }
    }

    onMouseDown(event) {
        this.mouseDownTarget = event.target;
    }

    clickOutside(event) {
        // Only close if BOTH mousedown and mouseup were on the dialog backdrop
        if (event.target !== this.dialogTarget || this.mouseDownTarget !== this.dialogTarget) {
            this.mouseDownTarget = null;
            return;
        }
        if (!this.#isClickInElement(event, this.dialogTarget)) {
            this.dialogTarget.close();
        }
        this.mouseDownTarget = null;
    }

    frameBusy() {
        this.frameTarget.dataset.loading = '';
    }

    frameIdle() {
        delete this.frameTarget.dataset.loading;
    }

    #applySizeFromContent() {
        const content = this.frameTarget.querySelector('[data-modal-size]');
        this.dialogTarget.dataset.modalSize = content?.dataset.modalSize || 'md';
    }

    #isClickInElement(event, element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top <= event.clientY &&
            event.clientY <= rect.top + rect.height &&
            rect.left <= event.clientX &&
            event.clientX <= rect.left + rect.width
        );
    }
}
