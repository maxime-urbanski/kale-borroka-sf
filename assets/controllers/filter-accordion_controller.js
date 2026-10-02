import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ['icon', 'body']

  open(event) {
    const opened = this.bodyTarget.classList.toggle('opened')

    event.currentTarget.setAttribute('aria-expanded', opened ? 'true' : 'false')
    this.iconTarget.classList.toggle('bi-dash-lg', opened)
    this.iconTarget.classList.toggle('bi-plus-lg', !opened)
  }
}
