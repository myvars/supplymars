import { Controller } from '@hotwired/stimulus';
import { Drawer } from 'flowbite';

export default class extends Controller {
    static values = {
        drawerId: String,
    };

    connect() {
        const $targetEl = document.getElementById(this.drawerIdValue);

        const options = {
            placement: 'left',
            backdrop: true,
            bodyScrolling: false,
            backdropClasses: 'bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-30',
            onHide: () => {},
            onShow: () => {},
            onToggle: () => {},
        };

        // Initialize the Drawer only if it hasn't been initialized yet
        if (!this.drawer) {
            this.drawer = new Drawer($targetEl, options);
        }
    }

    open() {
        this.drawer.show();
    }

    close() {
        this.drawer.hide();
    }
}