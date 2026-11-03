import {Controller} from "@hotwired/stimulus";

// Mobile filter panel: « Filtres » opens the filter column as a modal dialog (focus kept
// inside, Escape and the backdrop close it, focus back on the button). Without JS the
// column stays in the page and « Filtres » is a link down to it (#filtres).
const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), summary, [tabindex]:not([tabindex="-1"])'

export default class extends Controller {
  static targets = ['panel', 'dialog', 'opener', 'close']

  connect() {
    this.panelTarget.classList.add('is-enhanced')
    this.closeTargets.forEach((button) => { button.hidden = false })
    this.onKeydown = this.keydown.bind(this)
  }

  disconnect() {
    this.close()
  }

  open(event) {
    // Desktop: the column is always there, the link only scrolls.
    if (window.matchMedia('(min-width: 992px)').matches) {
      return
    }
    event.preventDefault()
    this.returnFocus = event.currentTarget

    this.panelTarget.classList.add('is-open')
    this.backdrop.hidden = false
    this.dialogTarget.setAttribute('role', 'dialog')
    this.dialogTarget.setAttribute('aria-modal', 'true')
    this.dialogTarget.setAttribute('aria-labelledby', 'filters-title')
    document.body.classList.add('kb-no-scroll')
    document.addEventListener('keydown', this.onKeydown)

    this.focusables()[0]?.focus()
  }

  close() {
    if (!this.hasPanelTarget || !this.panelTarget.classList.contains('is-open')) {
      return
    }
    this.panelTarget.classList.remove('is-open')
    this.backdrop.hidden = true
    this.dialogTarget.removeAttribute('role')
    this.dialogTarget.removeAttribute('aria-modal')
    this.dialogTarget.removeAttribute('aria-labelledby')
    document.body.classList.remove('kb-no-scroll')
    document.removeEventListener('keydown', this.onKeydown)

    this.returnFocus?.focus()
  }

  keydown(event) {
    if ('Escape' === event.key) {
      event.preventDefault()
      this.close()

      return
    }
    if ('Tab' !== event.key) {
      return
    }
    const focusables = this.focusables()
    const first = focusables[0]
    const last = focusables[focusables.length - 1]
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault()
      last.focus()
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault()
      first.focus()
    }
  }

  focusables() {
    return [...this.dialogTarget.querySelectorAll(FOCUSABLE)].filter((element) => null !== element.offsetParent)
  }

  get backdrop() {
    return this.panelTarget.querySelector('.kb-filters-panel__backdrop')
  }
}
