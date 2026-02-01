import './stimulus_bootstrap.js';
import './styles/app.css';
import { shouldPerformTransition, performTransition } from 'turbo-view-transitions';

let skipNextRenderTransition = false;

document.addEventListener('turbo:before-render', (event) => {
    if (shouldPerformTransition() && !skipNextRenderTransition) {
        event.preventDefault();
        performTransition(document.body, event.detail.newBody, async () => {
            await event.detail.resume();
        });
    }
});

document.addEventListener('turbo:before-frame-render', (event) => {
   if (shouldPerformTransition() && !event.target.hasAttribute('data-skip-transition')) {
        event.preventDefault();

        skipNextRenderTransition = true;
        setTimeout(() => {
            skipNextRenderTransition = false;
        }, 100);

        performTransition(event.target, event.detail.newFrame, async () => {
            await event.detail.resume();
        });
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

