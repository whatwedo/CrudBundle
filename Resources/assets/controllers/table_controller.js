import { Controller } from 'stimulus';
import * as StickyThead from 'stickythead'

export default class extends Controller {
    connect() {
      StickyThead.apply([this.element], {
        scrollableArea: document.getElementById('content')
      });
    }
}

