import useWindowWidth from "./useWindowWidth";
import {useEffect, useState} from 'react'

function useElementTransition(option) {
  const {width, height} = useWindowWidth()

  const [elementVisible, setElementVisible] = useState(1)
  const [elementToScroll, setElementToScroll] = useState(1)

  useEffect(() => {
    if (width <= option.breakpoint.mobile[0]) {
      setElementVisible(option.responsive.mobile.elementVisible)
      setElementToScroll(option.responsive.mobile.elementToScroll)
    }
    if (width >= option.breakpoint.tablet[0] && width < option.breakpoint.tablet[1]) {
      setElementVisible(option.responsive.tablet.elementVisible)
      setElementToScroll(option.responsive.tablet.elementToScroll)
    }
    if (width >= option.breakpoint.computer[0]) {
      setElementVisible(option.responsive.computer.elementVisible)
      setElementToScroll(option.responsive.computer.elementToScroll)
    }
  }, [width]);

  return {
    elementVisible,
    elementToScroll
  }
}

export default useElementTransition
