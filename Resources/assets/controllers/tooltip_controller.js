import { Controller } from 'stimulus';
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

export default class extends Controller {
    static values = {
        title: String
    }

    connect() {
        tippy(this.element, {
            content: this.titleValue
        });
    }

    disconnect() {
        this.element._tippy.destroy()
    }
}
