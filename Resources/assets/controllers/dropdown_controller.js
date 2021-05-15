import { Controller } from 'stimulus';
import { useTransition } from 'stimulus-use';
import { useClickOutside } from 'stimulus-use'

export default class extends Controller {
    static targets = ['dropdown']

    connect() {
        /*
        useTransition(this, {
            element: this.dropdownTarget,
            enterActive: 'transition ease-out duration-100',
            enterFrom: 'transform opacity-0 scale-95',
            enterTo: 'transform opacity-100 scale-100',
            leaveActive: 'transition ease-in duration-75',
            leaveFrom: 'transform opacity-100 scale-100',
            leaveTo: 'transform opacity-0 scale-95',
            hiddenClass: '',
            transitioned: false,
        });
       */

        useClickOutside(this)
    }

    clickOutside(event) {
        if (!this.dropdownTarget.classList.contains('hidden')) {
            event.preventDefault()
            this.dropdownTarget.classList.add('hidden');
        }
    }

    toggle() {
        //this.toggleTransition();
        this.dropdownTarget.classList.toggle('hidden');
    }
}
