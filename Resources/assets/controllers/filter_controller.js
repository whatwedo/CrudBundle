import { Controller } from 'stimulus';
import { useTransition } from 'stimulus-use';

export default class extends Controller {
    static targets = ['filters', 'filterGroupList', 'singleFilterRemove', 'filterGroupFilterHeaderFirst', 'filterGroupFilterHeaderOthers']

    connect() {
/*
        useTransition(this, {
            element: this.filtersTarget,
            enterActive: 'ease-in-out duration-500',
            enterFrom: 'opacity-0',
            enterTo: 'opacity-100',
            leaveActive: 'ease-in-out duration-500',
            leaveFrom: 'opacity-100',
            leaveTo: 'opacity-0',
            hiddenClass: '',
            transitioned: false,
        });

        useTransition(this, {
            element: this.filtersTarget,
            enterActive: 'transform transition ease-in-out duration-500 sm:duration-700',
            enterFrom: 'translate-x-full',
            enterTo: 'translate-x-0',
            leaveActive: 'transform transition ease-in-out duration-500 sm:duration-700',
            leaveFrom: 'translate-x-0',
            leaveTo: 'translate-x-full',
            transitioned: false,
        });
*/
        this.updateGui();
    }

    /*
     * open filter panel
     */
    open() {
      console.log('hoi');
        this.filtersTarget.classList.remove('hidden');
    }

    /*
     * close filter panel
     */
    close() {
        this.filtersTarget.classList.add('hidden');
    }

    /*
     * clone AND filter and append it at the end
     */
    appendAnd(event) {
        // clone and reset all values
        let node = event.target.closest('[data-whatwedo--crud-bundle--filter-target="singleFilter"]').cloneNode(true);
        this.resetInputs(node);

        event.target.closest('[data-whatwedo--crud-bundle--filter-target="filterGroupFilterList"]').appendChild(node);

        this.updateGui();
    }

    /*
     * remove AND-filter
     */
    removeAnd(event) {
        let filter = event.target.closest('[data-whatwedo--crud-bundle--filter-target="singleFilter"]');
        let filterGroup = filter.closest('[data-whatwedo--crud-bundle--filter-target="filterGroup"]');

        event.target.closest('[data-whatwedo--crud-bundle--filter-target="singleFilter"]').remove();

        // remove empty OR queries
        if (filterGroup.querySelectorAll('[data-whatwedo--crud-bundle--filter-target="singleFilter"]').length === 0) {
            filterGroup.remove();
        }

        this.updateGui();
    }

    /*
     * clone AND filter and append it at the end
     */
    appendOr(event) {
        // clone, only keep one filter and reset all values
        let node = this.filterGroupListTarget.querySelector('[data-whatwedo--crud-bundle--filter-target="filterGroup"]').cloneNode(true);
        node.querySelectorAll('[data-whatwedo--crud-bundle--filter-target="singleFilter"]:not(:first-child)').forEach(element => element.remove());
        this.resetInputs(node);

        this.filterGroupListTarget.appendChild(node);

        this.updateGui();
    }

    /*
     * resets the content of newly added filters
     */
    resetInputs(node) {
        node.querySelectorAll('input').forEach(element => element.value = null);
        node.querySelectorAll('select').forEach(element => element.selectedIndex = 0);
    }

    /*
     * updates the gui state
     */
    updateGui() {
        // only show "and" in the last row
        this.filterGroupListTargets.forEach(function (filterGroupList) {
            filterGroupList.querySelectorAll('[data-whatwedo--crud-bundle--filter-target="singleFilter"]')
              .forEach(e => e.querySelector('[data-whatwedo--crud-bundle--filter-target="singleFilterAnd"]').classList.add('hidden'))
            filterGroupList.querySelectorAll('[data-whatwedo--crud-bundle--filter-target="singleFilter"]:last-child')
              .forEach(e => e.querySelector('[data-whatwedo--crud-bundle--filter-target="singleFilterAnd"]').classList.remove('hidden'))
        });

        // only show "trash" when there is more than one filter
        if (this.singleFilterRemoveTargets.length === 1) {
            this.singleFilterRemoveTarget.classList.add('invisible')
        } else {
            this.singleFilterRemoveTargets.forEach(element => element.classList.remove('invisible'))
        }

        // switch headers
        this.filterGroupFilterHeaderFirstTargets.forEach(element => element.classList.add('hidden'));
        this.filterGroupFilterHeaderOthersTargets.forEach(element => element.classList.remove('hidden'));
        this.filterGroupFilterHeaderFirstTarget.classList.remove('hidden');
        this.filterGroupFilterHeaderOthersTarget.classList.add('hidden');
    }
}
