import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
  static values = {
    category: String,
    id: Number
  }

  remove() {
    const url = new URL(window.location.href)
    const searchParams = url.searchParams
    url.search = new URLSearchParams(
      [...searchParams].filter(([key, value]) => !(key.includes(this.categoryValue) && value === this.idValue.toString())
      )).toString()
    window.location.href = url.toString()
  }
}
