import {Controller} from "@hotwired/stimulus";

// « Voir N résultats » of the mobile filter panel, counted by the server (/catalog/count)
// for the boxes ticked so far. Without JS the button reads « Voir les résultats ».
export default class extends Controller {
  static targets = ['label']
  static values = {url: String}

  connect() {
    this.update()
  }

  disconnect() {
    clearTimeout(this.timer)
    this.abort?.abort()
  }

  update(event) {
    // Typing in the artist search narrows the list, not the results.
    if (event?.target?.name === 'artistSearch') {
      return
    }
    clearTimeout(this.timer)
    this.timer = setTimeout(() => this.fetch(), 150)
  }

  async fetch() {
    this.abort?.abort()
    this.abort = new AbortController()

    const params = new URLSearchParams(new FormData(this.element))
    params.delete('artistSearch')

    try {
      const response = await fetch(`${this.urlValue}?${params}`, {
        headers: {Accept: 'application/json'},
        signal: this.abort.signal,
      })
      if (!response.ok) {
        return
      }
      const {count} = await response.json()
      this.labelTarget.textContent = 0 === count ? 'Aucun résultat' : `Voir ${count} résultat${count > 1 ? 's' : ''}`
    } catch {
      // Aborted or offline: the label stays as it was.
    }
  }
}
