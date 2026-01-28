import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];

    connect() {
        this.close = this.close.bind(this);
        this._onClickOutside = this._onClickOutside.bind(this);

        document.addEventListener('click', this._onClickOutside);
        document.addEventListener('turbo:before-render', this.close);
    }

    disconnect() {
        document.removeEventListener('click', this._onClickOutside);
        document.removeEventListener('turbo:before-render', this.close);
    }

    toggle(event) {
        event.stopPropagation();
        this.menuTarget.classList.toggle('hidden');
    }

    close() {
        this.menuTarget.classList.add('hidden');
    }

    _onClickOutside(event) {
        if (!this.element.contains(event.target)) {
            this.close();
        }
    }
}
