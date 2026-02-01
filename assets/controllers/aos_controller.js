import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    async connect() {
        const [AOS, _] = await Promise.all([
            import('aos'),
            import('aos/dist/aos.css'),
        ]);
        AOS.default.init({ once: true });
    }
}
