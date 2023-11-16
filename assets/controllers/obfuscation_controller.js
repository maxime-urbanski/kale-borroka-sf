import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
  static values = {
    url: String
  }

  obfuscation() {
    window.location.href = this.urlValue;
  }
}
