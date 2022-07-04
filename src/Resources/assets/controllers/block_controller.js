import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['titleDiv', 'blockDiv'];

    static values = {
        collapsible: Boolean
    }

    connect() {
        if (this.collapsibleValue && this.hasTitleDivTarget && this.hasBlockDivTarget) {
            this.titleDivTarget.style.cursor = 'pointer';
            this.titleDivTarget.addEventListener('click', this.toggleBlock.bind(this));
        }
    }

    toggleBlock() {
        this.blockDivTarget.classList.toggle('hidden');
    }
}
