import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ['input']

  less(e) {
    const input = this.inputTarget
    input.value = parseInt(input.value) - 1

    if (parseInt(input.value) < 1) input.value = 1
  }

  more(e) {
    const input = this.inputTarget
    input.value = parseInt(input.value) + 1

    if (parseInt(input.value) > parseInt(input.max)) input.value = input.max
  }
}
