import './stimulus_bootstrap.js';
import './styles/app.css';
import { StreamActions, visit } from '@hotwired/turbo';

StreamActions.redirect = function() {
    visit(this.getAttribute('url'), { action: 'advance' });
};

document.addEventListener('turbo:before-frame-render', (event) => {
    if (document.startViewTransition) {
        event.preventDefault();
        document.startViewTransition(() => event.detail.resume());
    }
});

let flowbitePromise;
function initFlowbiteLazy() {
    if (!flowbitePromise) {
        flowbitePromise = import('flowbite').then(m => m.initFlowbite);
    }
    flowbitePromise.then(init => init());
}

document.addEventListener('turbo:load', () => {
    initFlowbiteLazy();
});

document.addEventListener('turbo:render', () => {
    initFlowbiteLazy();
});

document.addEventListener('turbo:frame-render', () => {
    initFlowbiteLazy();
});

