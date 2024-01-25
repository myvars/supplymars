import { Controller } from '@hotwired/stimulus';
import AOS from 'aos';

export default class extends Controller {
    connect() {
        AOS.init();
        // console.log('AOS controller running');
    }
}