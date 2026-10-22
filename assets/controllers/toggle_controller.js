import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ['toggleable'];

    toggle() {
        this.toggleableTarget.classList.toggle('hidden');
    }

    close() {
        this.toggleableTarget.classList.add('hidden');
    }

    preventToggle(event) {
        event.stopPropagation();
    }
}
