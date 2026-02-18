import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ['toggleable'];
    static values = {
        closeOnClickOutside: { type: Boolean, default: false },
    };

    connect() {
        if (this.closeOnClickOutsideValue) {
            this._onClickOutside = this._onClickOutside.bind(this);
            this._close = this.close.bind(this);
            document.addEventListener('click', this._onClickOutside);
            document.addEventListener('turbo:before-render', this._close);
        }
    }

    disconnect() {
        if (this._onClickOutside) {
            document.removeEventListener('click', this._onClickOutside);
            document.removeEventListener('turbo:before-render', this._close);
        }
    }

    toggle() {
        this.toggleableTarget.classList.toggle('hidden');
    }

    close() {
        this.toggleableTarget.classList.add('hidden');
    }

    preventToggle(event) {
        event.stopPropagation();
    }

    _onClickOutside(event) {
        if (!this.element.contains(event.target)) {
            this.close();
        }
    }
}
