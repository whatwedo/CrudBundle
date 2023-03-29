import { Controller } from '@hotwired/stimulus';
import 'regenerator-runtime/runtime'

let ajaxUrl = null;

export default class extends Controller {

    static targets = ['ajax'];

    connect() {
        ajaxUrl  = this.element.getAttribute('data-ajax-url');
        this.ajaxTargets.forEach(target => {
            let found = false;
            const formElement = target.querySelectorAll('select, input, textarea');
            if (formElement !== null) {
                formElement.forEach(element => {
                    this.initFormElement(element);
                    found = true;
                });
            }

            if (!found) {
                console.warn('could not whatwedo ajaxifiy this field:');
                console.warn(target);
            }
        });
    }

    submitAjax() {
        const form = this.element.closest('form');
        fetch(ajaxUrl, {
            method: form.method,
            body: new FormData(form),
        })
            .then(async response => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(await response.text(), 'text/html');
                doc.querySelectorAll('h1').forEach((element) => element.remove());
                form.parentNode.replaceChild(doc.body, form);
            })
        ;
    }

    initFormElement(formElement) {
        formElement.onchange = this.submitAjax.bind(this);
    }

}
