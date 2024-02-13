import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
  static values = {
    id: Number,
    user: Number,
    method: String
  }

  async action() {
    let url
    switch (this.methodValue) {
      case 'PATCH':
        url = `/mon-compte/mes-adresses/update/default-address/${this.userValue}/${this.idValue}`
        break;
      case 'DELETE':
        url = `/mon-compte/mes-adresses/remove/${this.idValue}`
        break;
      default:
        break;
    }
    await fetch(url, {
      method: this.methodValue
    }).then(response => window.location.href = response.url)
  }
}
