import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['down', 'right', 'children'];

    // false = closed
    // true = open
    state = false;

    connect() {
        this.downTarget.classList.add('hidden');
        this.childrenTarget.classList.add('hidden');
    }

    toggle() {
        if (this.state) {
            this.downTarget.classList.add('hidden');
            this.rightTarget.classList.remove('hidden');
            this.childrenTarget.classList.add('hidden');
        } else {
            this.downTarget.classList.remove('hidden');
            this.rightTarget.classList.add('hidden');
            this.childrenTarget.classList.remove('hidden');
        }
        this.state = !this.state;
    }

}
