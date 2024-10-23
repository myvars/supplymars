import { Controller } from "@hotwired/stimulus"
import debounce from 'debounce'

export default class extends Controller {
    static values = {
        url: String,
        frame: String // The ID of the target Turbo Frame
    }

    initialize() {
        // Debounce the preload function
        this.debouncedPreload = debounce(this.preload.bind(this), 100);
        this.preloaded = false; // A flag to ensure only one preload happens
    }

    // Handles the click event on the container
    navigate(event) {
        if (event.target.closest('a')) {
            return;
        }

        const url = this.urlValue;
        const frameId = this.frameValue;

        if (url && frameId) {
            Turbo.visit(url, { frame: frameId });
        }
    }

    // Preload the URL by manually fetching the content
    preload() {
        // Check if the content is already preloaded
        if (this.preloaded) return;

        const url = this.urlValue;
        if (url) {
            fetch(url, {
                headers: { 'Turbo-Frame': this.frameValue },
            })
                .then(response => response.text())
                .then(html => {
                    this.preloaded = true; // Set the flag to prevent multiple preloads
                })
                .catch(error => {
                    console.error("Error preloading content:", error);
                });
        }
    }

    // Trigger debounced preload on mouseenter
    mouseenter() {
        if (!window.Turbo) {
            return;
        }
        this.debouncedPreload();
    }
}