import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    async connect() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const [AOS, _] = await Promise.all([
            import('aos'),
            import('aos/dist/aos.css'),
        ]);
        AOS.default.init({ once: true });
    }
}
