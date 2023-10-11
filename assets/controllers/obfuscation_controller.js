import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
  static values = {
    url: String
  }

  obfuscation() {
    console.log(this.urlValue)
    window.location.href = this.urlValue;
  }
}
