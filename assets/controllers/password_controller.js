/* stimulusFetch: 'lazy' */
import {Controller} from "@hotwired/stimulus";

// Password field: show/hide toggle, and the rules listed under it ticked as one types.
// A confirmation field (confirms value: the id of the new password) says as one types that the
// two differ: as soon as it stops being the start of the new password, or once it is left.
// Without JavaScript the field and its rules still work; the toggle and the meter stay hidden.
export default class extends Controller {
  static targets = ['input', 'toggle', 'meter', 'rule', 'mismatch']
  static values = {confirms: String, mismatch: String}

  connect() {
    if (this.hasToggleTarget) {
      this.toggleTarget.hidden = false
    }
    if (this.hasMeterTarget) {
      this.meterTarget.hidden = false
    }
    if (this.confirmsValue) {
      this.original = document.getElementById(this.confirmsValue)
      this.compareLater = () => this.compare(false)
      this.compareNow = () => this.compare(true)
      this.original?.addEventListener('input', this.compareLater)
      this.inputTarget.addEventListener('blur', this.compareNow)
    }
    this.check()
  }

  disconnect() {
    this.original?.removeEventListener('input', this.compareLater)
    this.inputTarget.removeEventListener('blur', this.compareNow)
  }

  toggle() {
    const show = this.inputTarget.type === 'password'
    this.inputTarget.type = show ? 'text' : 'password'
    this.toggleTarget.setAttribute('aria-pressed', String(show))
    this.toggleTarget.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye'
  }

  check() {
    if (this.confirmsValue) {
      this.compare(false)
    }

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

  // finished: the field was left, so a confirmation shorter than the password is a gap too.
  compare(finished) {
    if (!this.original || !this.hasMismatchTarget) {
      return
    }

    const value = this.inputTarget.value
    const expected = this.original.value
    const differs = value !== '' && (finished ? value !== expected : !expected.startsWith(value))

    this.inputTarget.classList.toggle('is-invalid', differs)
    if (differs) {
      this.inputTarget.setAttribute('aria-invalid', 'true')
    } else {
      this.inputTarget.removeAttribute('aria-invalid')
    }
    // Only rewritten when it changes, so it is announced once.
    const message = differs ? this.mismatchValue : ''
    if (this.mismatchTarget.textContent !== message) {
      this.mismatchTarget.textContent = message
    }
  }
}
