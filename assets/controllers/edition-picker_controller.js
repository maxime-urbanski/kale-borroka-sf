import {Controller} from "@hotwired/stimulus";

/**
 * Switches between the editions of an album — LP black, LP red, CD — without reloading.
 *
 * Every edition is already rendered server-side and all but one are hidden, so this only
 * toggles visibility and retargets the add-to-cart form. Nothing is templated here, which
 * is what keeps the client from drifting away from what the server rendered. The picker
 * entries are real links, so the page degrades to a normal navigation without JS.
 */
export default class extends Controller {
  static targets = ['cover', 'choice', 'panel', 'offer', 'articleId']
  static values = {selected: String}

  connect() {
    this.syncForm()
  }

  select(event) {
    event.preventDefault()

    const slug = event.currentTarget.dataset.edition
    if (!slug || slug === this.selectedValue) return

    this.selectedValue = slug
    this.show(slug)

    // Keep the URL shareable and the back button meaningful.
    const href = event.currentTarget.getAttribute('href')
    if (href) window.history.replaceState({}, '', href)
  }

  selectOffer() {
    this.syncForm()
  }

  show(slug) {
    this.coverTargets.forEach(el => el.hidden = el.dataset.edition !== slug)
    this.panelTargets.forEach(el => el.hidden = el.dataset.edition !== slug)
    this.choiceTargets.forEach(el => el.classList.toggle('active', el.dataset.edition === slug))

    this.syncForm()
  }

  /**
   * Points the cart form at the offer checked inside the visible edition, and clamps the
   * quantity input to that offer's stock.
   */
  syncForm() {
    if (!this.hasArticleIdTarget) return

    const offer = this.offerTargets.find(
      input => input.dataset.edition === this.selectedValue && input.checked
    )

    if (!offer) return

    this.articleIdTarget.value = offer.dataset.articleId

    const quantity = this.element.querySelector('[data-add-to-cart-target="input"]')
    if (!quantity) return

    const available = parseInt(offer.dataset.quantity, 10) || 0
    quantity.max = available

    if (parseInt(quantity.value, 10) > available) quantity.value = available
  }
}
