import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = ['tab', 'content'];

    connect() {
        const firstElement = this.element.querySelector('[data-tab-id]:first-child');
        this.activeTab(firstElement);
    }

    disconnect() {

    }

    openTab(event) {
        this.resetContent();
        this.activeTab(event.currentTarget);
    }

    resetContent() {
        this.tabTargets.forEach(tab => {
            tab.classList.remove('active');
            tab.querySelector('[data-whatwedo--crud-bundle--tab-target]').classList.remove('bg-primary-500');
        });
        this.contentTargets.forEach(content => {
            content.classList.remove('block');
            content.classList.add('hidden');
        });
    }

    activeTab(element) {
        const tabId = element.dataset.tabId;
        const activeContent = this.element.querySelector(`[data-tab-content="${tabId}"]`);
        const underline = element.querySelector('[data-whatwedo--crud-bundle--tab-target]');

        element.classList.remove('text-neutral-500');
        element.classList.add('text-neutral-900');
        element.classList.add('active');

        underline.classList.remove('bg-transparent');
        underline.classList.add('bg-primary-500');

        activeContent.classList.remove('hidden');
        activeContent.classList.add('block');
    }
}
