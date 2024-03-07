import { Controller } from '@hotwired/stimulus';
import Dropzone from "dropzone";
import 'dropzone/dist/dropzone.css'


export default class extends Controller {
    static values = {
        url: String,
        paramName: String
    };

    connect() {
        const dropzone = new Dropzone(this.element, {
            url: this.urlValue,
            paramName: this.paramNameValue, // The name that will be used to transfer the file
            maxFiles: 10,
            maxFilesize: 1, // MB
            addRemoveLinks: true,
        });

        dropzone.on("error", (file, message) => {
            this.dispatch('dropzone:error', {
                detail: { file, message }
            });
        });

        dropzone.on("complete", file => {
            this.dispatch('dropzone:uploaded', {
                detail: { file }
            });
        });
    }
}
