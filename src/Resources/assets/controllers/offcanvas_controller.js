import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['content', 'closeButton', 'backdrop']

    hideContent() {
        this.contentTarget.classList.remove('translate-x-0');
        this.closeButtonTarget.classList.remove('opacity-100', 'pointer-events-auto');
        this.backdropTarget.classList.remove('opacity-100');

        this.contentTarget.classList.add('-translate-x-full');
        this.closeButtonTarget.classList.add('opacity-0', 'pointer-events-none');
        this.backdropTarget.classList.add('opacity-0');
    }

    showContent() {
        this.contentTarget.classList.remove('-translate-x-full');
        this.closeButtonTarget.classList.remove('opacity-0', 'pointer-events-none');
        this.backdropTarget.classList.remove('opacity-0');

        this.contentTarget.classList.add('translate-x-0');
        this.closeButtonTarget.classList.add('opacity-100', 'pointer-events-auto');
        this.backdropTarget.classList.add('opacity-100');
    }
}
