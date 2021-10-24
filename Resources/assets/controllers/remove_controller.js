import { Controller } from 'stimulus';

export default class extends Controller {
    trigger() {
        this.element.remove();
    }
}
