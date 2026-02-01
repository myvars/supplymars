import { Controller } from '@hotwired/stimulus';
import Dropzone from "dropzone";
import 'dropzone/dist/dropzone.css'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        url: String,
        paramName: String,
    };

    connect() {
        const dropzone = new Dropzone(this.element, {
            url: this.urlValue,
            paramName: this.paramNameValue, // The name that will be used to transfer the file
            maxFiles: 10,
            maxFilesize: 2, // MB
            uploadMultiple: true,
            addRemoveLinks: true,
            acceptedFiles: "image/*",
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

        dropzone.on("completemultiple", file => {
            this.dispatch('dropzone:uploaded', {
                detail: { file }
            });
            // wait for 500ms before refreshing the page
            // to give the server time to process the uploaded files
            setTimeout(() => {
                this.turboRefresh();
            }, 500);
        });
    }

    turboRefresh() {
        if (window.Turbo) {
            Turbo.visit(window.location, {action: 'replace'});
        }
    }
}
