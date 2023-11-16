import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
  static values = {
    id: Number,
    user: Number
  }

  async patch(e) {
    window.location.href = `/mon-compte/mes-adresses/update/default-address/${this.userValue}/${this.idValue}`
  }
}
