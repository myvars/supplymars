import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        dependent: String, // #ID of the dependent field
        url: String // URL template with a placeholder, e.g., "/api/categories/%id%/subcategories"
    };

    async connect() {
        if (this.element.value) {
            await this.renderDependent(this.element.value);
        }
    }

    async updateDependent(event) {
        await this.renderDependent(event.target.value);
    }

    async renderDependent(sourceValue) {
        const dependent = document.getElementById(this.dependentValue);
        const url = this.urlValue.replace("%25id%25", sourceValue || 'null'); // Replace placeholder with the actual value
        console.log(url, sourceValue)
        try {
            const response = await fetch(url);
            if (response.ok) {
                dependent.innerHTML = await response.text(); // Update the dependent field with the response
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
}