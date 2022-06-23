import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['titleDiv', 'blockDiv'];

    static values = {
        collapsible: Boolean
    }

    connect() {
        if (this.collapsibleValue && this.hasTitleDivTarget && this.hasBlockDivTarget) {
            console.log(this.titleDivTarget);
            console.log(this.blockDivTarget);
        } else {
            console.log('not collapsible');
        }
    }
}
