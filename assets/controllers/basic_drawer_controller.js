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
            edge: false,
            edgeOffset: '',
            backdropClasses:
                'bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-30',
            onHide: () => {
//                console.log('drawer is hidden');
            },
            onShow: () => {
//                console.log('drawer is shown');
            },
            onToggle: () => {
//                console.log('drawer has been toggled');
            },
        };

        // instance options object
        const instanceOptions = {
            id: this.drawerIdValue,
            override: true
        };

        if (this.drawer === undefined) {
            this.drawer = new Drawer($targetEl, options, instanceOptions);
        }
    }

    open() {
        this.drawer.show();
    }

    close() {
        this.drawer.hide();
    }
}