import { Controller } from '@hotwired/stimulus';
import { Drawer } from 'flowbite';

export default class extends Controller {
    static values = {
        drawerId: String,
        frameId: String,
    };

    connect() {
        const $targetEl = document.getElementById(this.drawerIdValue);

        const options = {
            placement: 'right',
            backdrop: true,
            bodyScrolling: false,
            backdropClasses: 'bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-30',
            onHide: () => { this.isOpen = false; },
            onShow: () => { this.isOpen = true; },
        };

        if (!this.drawer) {
            this.drawer = new Drawer($targetEl, options);
            this.isOpen = false;
        }
    }

    open() {
        const frame = document.getElementById(this.frameIdValue);
        const helpUrl = '/help?page=' + encodeURIComponent(window.location.pathname);

        if (frame) {
            frame.src = helpUrl;
        }

        this.drawer.show();
    }

    close() {
        this.drawer.hide();
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
