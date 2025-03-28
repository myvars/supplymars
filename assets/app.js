import './bootstrap.js';
import './styles/app.css';
import 'aos/dist/aos.css'
import { shouldPerformTransition, performTransition } from 'turbo-view-transitions';
import { initFlowbite } from 'flowbite';

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

document.addEventListener('turbo:load', () => {
    initFlowbite();
});

document.addEventListener('turbo:render', () => {
    initFlowbite();
});

document.addEventListener('turbo:frame-render', () => {
    initFlowbite();
});

