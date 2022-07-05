import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['title', 'block'];

    static values = {
        collapsible: Boolean
    }

    connect() {
        if (this.collapsibleValue && this.hasTitleTarget && this.hasBlockTarget) {
            this.titleTarget.style.cursor = 'pointer';
            this.titleTarget.addEventListener('click', this.toggleBlock.bind(this));
        }
    }

    toggleBlock() {
        this.blockTarget.classList.toggle('hidden');
    }
}
