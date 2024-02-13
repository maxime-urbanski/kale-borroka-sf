import {Controller, del} from "@hotwired/stimulus";

export default class extends Controller {
  static values = {
    id: Number,
    user: Number,
    method: String
  }

  async patch(e) {
    window.location.href =
      `/mon-compte/mes-adresses/update/default-address/${this.userValue}/${this.idValue}`
  }

  async delete() {
    await fetch(`/mon-compte/mes-adresses/remove/${this.idValue}`, {
      method: "delete",
      redirect: "follow"
    })

    window.location.href = '/mon-compte/mes-adresses/'
  }
}
