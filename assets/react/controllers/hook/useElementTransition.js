import {useWindowWidth} from "./useWindowWidth";
import {useEffect, useState} from 'react'

export function useElementTransition(option) {
  const {width, height} = useWindowWidth()

  const [elementVisible, setElementVisible] = useState(1)
  const [elementToScroll, setElementToScroll] = useState(1)

  useEffect(() => {
    if (width <= option.breakpoint.mobile[0]) {
      setElementVisible(value => value = option.responsive.mobile.elementVisible)
      setElementToScroll(value => value = option.responsive.mobile.elementToScroll)
    }
    if (width >= option.breakpoint.tablet[0] && width < option.breakpoint.tablet[1]) {
      setElementVisible(value => value = option.responsive.tablet.elementVisible)
      setElementToScroll(value => value = option.responsive.tablet.elementToScroll)
    }
    if (width >= option.breakpoint.computer[0]) {
      setElementVisible(value => value = option.responsive.computer.elementVisible)
      setElementToScroll(value => value = option.responsive.computer.elementToScroll)
    }
  }, [width]);

  return [
    elementVisible,
    elementToScroll
  ]
}
