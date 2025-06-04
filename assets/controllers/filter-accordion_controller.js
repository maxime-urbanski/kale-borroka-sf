import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ['icon', 'body']
  open(e) {
    this.bodyTarget.classList.toggle('opened')
    this.iconTarget.classList.toggle('rotate-180')
  }
}
