import {Controller} from "@hotwired/stimulus";

export default class extends Controller {

  open(e) {
    const filterBody = this.element.lastElementChild
    filterBody.classList.toggle('opened')
    const chevron = e.currentTarget
    chevron.classList.toggle('rotate-180')
  }
}
