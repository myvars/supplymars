import { Controller } from '@hotwired/stimulus';

const TOP_LINK = {
    active: 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300',
    inactive: 'text-gray-900 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800',
};

const CHILD_LINK = {
    active: 'font-medium bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300',
    inactive: 'text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200',
};

const ICON = {
    active: 'text-primary-500 dark:text-primary-400',
    inactive: 'text-gray-500 dark:text-gray-400',
};

const CHEVRON = {
    active: 'text-primary-400',
    inactive: 'text-gray-400',
};

function swap(el, from, to) {
    el.classList.remove(...from.split(' '));
    el.classList.add(...to.split(' '));
}

export default class extends Controller {
    connect() {
        this.update();
    }

    update() {
        this.#resetAll();
        this.#activate();
    }

    #resetAll() {
        for (const a of this.element.querySelectorAll('a[data-nav="top"]')) {
            swap(a, TOP_LINK.active, TOP_LINK.inactive);
            a.removeAttribute('aria-current');
            const icon = a.querySelector('[data-nav="icon"]');
            if (icon) swap(icon, ICON.active, ICON.inactive);
        }

        for (const btn of this.element.querySelectorAll('button[data-nav="section"]')) {
            swap(btn, TOP_LINK.active, TOP_LINK.inactive);
            const icon = btn.querySelector('[data-nav="icon"]');
            if (icon) swap(icon, ICON.active, ICON.inactive);
            const chevron = btn.querySelector('[data-nav="chevron"]');
            if (chevron) swap(chevron, CHEVRON.active, CHEVRON.inactive);
        }

        for (const a of this.element.querySelectorAll('a[data-nav="child"]')) {
            swap(a, CHILD_LINK.active, CHILD_LINK.inactive);
            a.removeAttribute('aria-current');
        }

        for (const ul of this.element.querySelectorAll('ul[data-nav="dropdown"]')) {
            ul.classList.add('hidden');
        }
    }

    #activate() {
        const path = window.location.pathname;
        const search = window.location.search;

        let bestLink = null;
        let bestScore = 0;

        for (const a of this.element.querySelectorAll('a[data-turbo-frame="body"]')) {
            const url = new URL(a.href);
            const linkPath = url.pathname;
            const linkSearch = url.search;

            const pathMatches = linkPath === '/'
                ? path === '/'
                : path.startsWith(linkPath);

            if (!pathMatches) continue;

            // If link has query params, require them to match
            if (linkSearch && linkSearch !== search) continue;

            // Prefer links with matching query params over those without
            const score = linkPath.length + (linkSearch && linkSearch === search ? 1000 : 0);
            if (score > bestScore) {
                bestScore = score;
                bestLink = a;
            }
        }

        if (!bestLink) return;

        const navType = bestLink.dataset.nav;

        if (navType === 'top') {
            swap(bestLink, TOP_LINK.inactive, TOP_LINK.active);
            bestLink.setAttribute('aria-current', 'page');
            const icon = bestLink.querySelector('[data-nav="icon"]');
            if (icon) swap(icon, ICON.inactive, ICON.active);
        } else if (navType === 'child') {
            swap(bestLink, CHILD_LINK.inactive, CHILD_LINK.active);
            bestLink.setAttribute('aria-current', 'page');

            const dropdown = bestLink.closest('ul[data-nav="dropdown"]');
            if (dropdown) {
                dropdown.classList.remove('hidden');
                const btn = this.element.querySelector(`button[aria-controls="${dropdown.id}"]`);
                if (btn) {
                    swap(btn, TOP_LINK.inactive, TOP_LINK.active);
                    const icon = btn.querySelector('[data-nav="icon"]');
                    if (icon) swap(icon, ICON.inactive, ICON.active);
                    const chevron = btn.querySelector('[data-nav="chevron"]');
                    if (chevron) swap(chevron, CHEVRON.inactive, CHEVRON.active);
                }
            }
        }
    }
}
