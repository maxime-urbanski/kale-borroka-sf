import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
  static targets = [
    'main',
    'children',
    'next',
    'prev'
  ]

  connect() {
    const mainContainerHasScrollable = this.mainTarget.scrollWidth < this.childrenTarget.scrollWidth
    
    if (mainContainerHasScrollable) {
      this.nextTarget.classList.remove('hidden')

      this.childrenTarget.addEventListener('scroll', () => {
        const currentScrollValue = this.childrenTarget.scrollLeft
        const maxScrollValue = this.childrenTarget.scrollWidth - this.childrenTarget.clientWidth

        if (currentScrollValue === 0) {
          if (!this.prevTarget.classList.contains('hidden')) {
            this.prevTarget.classList.add('hidden')
          }
        }

        if (currentScrollValue > 0) {
          this.prevTarget.classList.remove('hidden')

          if (this.nextTarget.classList.contains('hidden')) {
            this.nextTarget.classList.remove('hidden')
          }
        }

        if (maxScrollValue === currentScrollValue) {
          this.nextTarget.classList.add('hidden')
        }
      })
    }
  }

  prev() {
    const maxScroll = this.childrenTarget.scrollWidth - this.childrenTarget.clientWidth;
    const current = this.childrenTarget.scrollLeft;
    const next = Math.min(current - 100, maxScroll);

    this.childrenTarget.scrollTo({
      behavior: 'smooth',
      left: next
    })
  }

  next() {
    const maxScroll = this.childrenTarget.scrollWidth - this.childrenTarget.clientWidth;
    const current = this.childrenTarget.scrollLeft;
    const next = Math.min(current + 100, maxScroll);

    this.childrenTarget.scrollTo({
      behavior: 'smooth',
      left: next
    })
  }
}
