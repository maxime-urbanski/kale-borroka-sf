import {Controller} from "@hotwired/stimulus";

// Copies an order's reference (maquettes 17, 18): the button only shows where the clipboard can be
// written, says « Copiée » for a moment and the status region tells screen readers; a refused
// copy says so too. Without JavaScript the button stays hidden and the reference can still be selected.
export default class extends Controller {
  static targets = ['source', 'button', 'icon', 'label', 'status']

  connect() {
    this.buttonTarget.hidden = !navigator.clipboard
  }

  disconnect() {
    clearTimeout(this.timeout)
  }

  async copy() {
    clearTimeout(this.timeout)

    try {
      await navigator.clipboard.writeText(this.sourceTarget.textContent.trim())
    } catch {
      this.show('bi-copy', 'Copier', 'Copie impossible : sélectionne la référence')

      return
    }

    this.show('bi-check-lg', 'Copiée', 'Référence copiée')
    this.timeout = setTimeout(() => this.show('bi-copy', 'Copier', ''), 2500)
  }

  show(icon, label, status) {
    this.iconTarget.className = `bi ${icon}`
    this.labelTarget.textContent = label
    this.statusTarget.textContent = status
  }
}
