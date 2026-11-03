import {Controller} from "@hotwired/stimulus";

// Remembers, for the visit, which filter groups (<details>) the visitor opened or closed.
// Opening and closing are native: this only restores the state on the next page.
const KEY = 'kb-filter-groups'

export default class extends Controller {
  static targets = ['group']

  connect() {
    const saved = this.read()
    this.groupTargets.forEach((group) => {
      const key = group.dataset.key
      // A group with a ticked box stays open.
      if (key in saved && !group.querySelector('input:checked')) {
        group.open = saved[key]
      }
      group.addEventListener('toggle', () => this.save(key, group.open))
    })
  }

  read() {
    try {
      return JSON.parse(sessionStorage.getItem(KEY) || '{}')
    } catch {
      return {}
    }
  }

  save(key, open) {
    try {
      sessionStorage.setItem(KEY, JSON.stringify({...this.read(), [key]: open}))
    } catch {
      // Storage unavailable (private mode): nothing to remember.
    }
  }
}
