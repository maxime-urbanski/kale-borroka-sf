import {Controller} from "@hotwired/stimulus";

// Narrows the list of artists as the visitor types. Without JS the field is sent with the
// form and the server narrows the list (FacetCounter). Ticked artists always stay.
export default class extends Controller {
  static targets = ['input', 'option']

  filter() {
    const search = this.inputTarget.value.trim().toLocaleLowerCase('fr')
    this.optionTargets.forEach((option) => {
      const name = option.textContent.toLocaleLowerCase('fr')
      const checked = option.querySelector('input').checked
      option.hidden = '' !== search && !checked && !name.includes(search)
    })
    // The values behind « Voir les N autres »: open it while searching.
    this.element.querySelectorAll('details').forEach((more) => {
      if ('' !== search) {
        more.open = true
      }
    })
  }
}
