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

// iOS Safari swipe gesture handling
// Safari 18+ has built-in swipe animations that cannot be disabled.
// We disable Turbo's view transitions for restore visits (back/forward)
// to prevent double-animation jank with Safari's native swipe animation.
const isIOSSafari = /iPhone|iPad|iPod/.test(navigator.userAgent) &&
    /WebKit/.test(navigator.userAgent) &&
    !/CriOS|FxiOS|OPiOS|EdgiOS/.test(navigator.userAgent);

if (isIOSSafari) {
    const viewTransitionMeta = document.querySelector('meta[name="turbo-view-transition"]');

    // Disable view transitions for restore visits (back/forward navigation)
    document.addEventListener('turbo:visit', (event) => {
        if (event.detail.action === 'restore' && viewTransitionMeta) {
            viewTransitionMeta.setAttribute('content', 'false');
        }
    });

    // Re-enable view transitions after render completes
    document.addEventListener('turbo:render', () => {
        if (viewTransitionMeta) {
            viewTransitionMeta.setAttribute('content', 'true');
        }
    });
}

