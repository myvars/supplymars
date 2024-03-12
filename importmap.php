<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.4',
    ],
    'stimulus-use' => [
        'version' => '0.52.2',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'bootstrap' => [
        'version' => '5.3.3',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.3',
        'type' => 'css',
    ],
    'aos' => [
        'version' => '2.3.4',
    ],
    'lodash.throttle' => [
        'version' => '4.1.1',
    ],
    'lodash.debounce' => [
        'version' => '4.0.8',
    ],
    'aos/dist/aos.css' => [
        'version' => '2.3.4',
        'type' => 'css',
    ],
    'turbo-view-transitions' => [
        'version' => '0.3.0',
    ],
    'flowbite' => [
        'version' => '2.3.0',
    ],
    'flowbite/dist/flowbite.min.css' => [
        'version' => '2.3.0',
        'type' => 'css',
    ],
    'stimulus-popover' => [
        'version' => '6.2.0',
    ],
    'debounce' => [
        'version' => '2.0.0',
    ],
    'dropzone/dist/dropzone.css' => [
        'version' => '6.0.0-beta.2',
        'type' => 'css',
    ],
    'dropzone' => [
        'version' => '6.0.0-beta.2',
    ],
    'just-extend' => [
        'version' => '6.2.0',
    ],
];
