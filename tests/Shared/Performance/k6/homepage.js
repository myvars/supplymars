import http from 'k6/http';
import { check, sleep } from 'k6';
import { buildRequestUrls } from './resources/resolveAssets.js';

const BASE_URL = __ENV.DEFAULT_URI;

// Pages to test (not in manifest)
const pages = [
    '/', // homepage
];

// Logical asset names to resolve from manifest
const assetNames = [
    'styles/app.css',
    'vendor/aos/dist/aos.css',
    'vendor/dropzone/dist/dropzone.css',
    'app.js',
    'bootstrap.js',
    'vendor/@hotwired/turbo/turbo.index.js',
    'vendor/flowbite/flowbite.index.js',
    'vendor/chart.js/chart.js.index.js',
    'vendor/sortablejs/sortablejs.index.js',
    'vendor/dropzone/dropzone.index.js',
    'vendor/aos/aos.index.js',
    'vendor/@hotwired/stimulus/stimulus.index.js',
    'vendor/flowbite-datepicker/flowbite-datepicker.index.js',
    'vendor/debounce/debounce.index.js',
    'vendor/stimulus-use/stimulus-use.index.js',
    'vendor/@popperjs/core/core.index.js',
    'vendor/just-extend/just-extend.index.js',
    'vendor/lodash.throttle/lodash.throttle.index.js',
    'vendor/lodash.debounce/lodash.debounce.index.js',
    'vendor/@kurkle/color/color.index.js',
    'images/martian_landscape.webp'
];

const allRequests = buildRequestUrls({
    baseUrl: BASE_URL,
    pages,
    assetNames
});

export let options = {
    vus: 2, // virtual users
    duration: '30s',
};

export default function () {
    let requests = allRequests.map(url => ['GET', url]);
    let responses = http.batch(requests);

    // Check main page and a couple of key assets
    check(responses[0], { 'homepage 200': (r) => r.status === 200 });
    check(responses[1], { 'CSS 200': (r) => r.status === 200 });
    check(responses[4], { 'JS 200': (r) => r.status === 200 });

    sleep(1);
}
