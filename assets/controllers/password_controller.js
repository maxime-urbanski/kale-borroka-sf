/* stimulusFetch: 'lazy' */
import {Controller} from "@hotwired/stimulus";

// Password field: show/hide toggle, and the rules listed under it ticked as one types.
// Without JavaScript the field and its rules still work; the toggle and the meter stay hidden.
export default class extends Controller {
  static targets = ['input', 'toggle', 'meter', 'rule']

  connect() {
    if (this.hasToggleTarget) {
      this.toggleTarget.hidden = false
    }
    if (this.hasMeterTarget) {
      this.meterTarget.hidden = false
    }
    this.check()
  }

  toggle() {
    const show = this.inputTarget.type === 'password'
    this.inputTarget.type = show ? 'text' : 'password'
    this.toggleTarget.setAttribute('aria-pressed', String(show))
    this.toggleTarget.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye'
  }

  check() {
    if (!this.hasRuleTarget) {
      return
    }

    const value = this.inputTarget.value
    let met = 0

    this.ruleTargets.forEach((rule) => {
      const ok = rule.dataset.min
        ? [...value].length >= Number(rule.dataset.min)
        : new RegExp(rule.dataset.pattern, 'u').test(value)

      rule.classList.toggle('is-met', ok)
      rule.querySelector('i').className = ok ? 'bi bi-check-lg' : 'bi bi-dash'
      rule.querySelector('[data-state]').textContent = value === '' ? '' : (ok ? ' : respecté' : ' : pas encore')
      met += ok ? 1 : 0
    })

    if (this.hasMeterTarget) {
      // Two bars for the rules, one more for a long password, one more for mixed case.
      const long = [...value].length >= Number(this.ruleTargets[0].dataset.min || 0) + 4
      const mixed = /\p{Ll}/u.test(value) && /\p{Lu}/u.test(value)
      const score = value === '' ? 0 : met + (met === this.ruleTargets.length ? Number(long) + Number(mixed) : 0)

      this.meterTarget.querySelectorAll('span').forEach((bar, index) => {
        bar.classList.toggle('is-on', index < score)
      })
    }
  }
}
