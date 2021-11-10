import { Controller } from 'stimulus';

export default class extends Controller {
    static targets = ['arrow', 'navigation']

    toggle() {
        if (this.navigationTarget.classList.contains('hidden')) {
            this.arrowTarget.classList.remove('text-neutral-300');
            this.arrowTarget.classList.add('text-neutral-400');
            this.arrowTarget.classList.add('rotate-90');
            this.navigationTarget.classList.remove('hidden');
        } else {
            this.arrowTarget.classList.remove('text-neutral-400');
            this.arrowTarget.classList.remove('rotate-90');
            this.arrowTarget.classList.add('text-neutral-300');
            this.navigationTarget.classList.add('hidden');
        }
    }
}
