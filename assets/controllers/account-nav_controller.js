import {Controller} from "@hotwired/stimulus";

// Account menu: below lg a row of chips scrolling sideways, where the current page may sit past
// the right edge. Scrolls the row (not the page) so that it starts with the current chip.
// From lg up the menu is a column and nothing scrolls. Without JavaScript the row stays at its start.
export default class extends Controller {
  connect() {
    const current = this.element.querySelector('[aria-current="page"]')

    if (!current || this.element.scrollWidth <= this.element.clientWidth) {
      return
    }

    this.element.scrollLeft += current.getBoundingClientRect().left - this.element.getBoundingClientRect().left
  }
}
