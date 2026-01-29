import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['sunIcon', 'moonIcon'];

    connect() {
        this.applyIcons();
    }

    toggle() {
        document.documentElement.classList.toggle('dark');
        const theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        localStorage.setItem('theme', theme);
        this.applyIcons();
    }

    applyIcons() {
        const isDark = document.documentElement.classList.contains('dark');
        this.sunIconTarget.classList.toggle('hidden', !isDark);
        this.moonIconTarget.classList.toggle('hidden', isDark);
    }
}
