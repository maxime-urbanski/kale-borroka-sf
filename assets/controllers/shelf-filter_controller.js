import {Controller} from "@hotwired/stimulus";

/*
 * Homepage « Derniers articles »: a chip shows its shelf and hides the others. The chips
 * are hidden in the HTML, so that without JavaScript the « Tout » shelf simply shows alone.
 */
export default class extends Controller {
  static targets = ['chips', 'chip', 'shelf']

  connect() {
    if (this.hasChipsTarget) {
      this.chipsTarget.hidden = false
    }
  }

  show(event) {
    const shelfId = event.currentTarget.getAttribute('aria-controls')

    this.chipTargets.forEach(chip => {
      chip.setAttribute('aria-pressed', chip === event.currentTarget ? 'true' : 'false')
    })
    this.shelfTargets.forEach(shelf => {
      shelf.hidden = shelf.id !== shelfId
    })
  }
}
