import {useEffect, useState} from "react";
import {useElementTransition} from "./useElementTransition";

export default function useInfinite(products, options) {
  const {infinite} = options
  const {elementVisible} = useElementTransition(options)
  const parseProducts = typeof products === "string" ? JSON.parse(products) : products

  const offset = 2 * elementVisible - 1
  const [elements, setElements] = useState(parseProducts)
  const [currentIndex, setCurrentIndex] = useState(firstSliderItem)

  useEffect(() => {
    if (infinite) {
      setElements( value => [
        ...value.slice(-offset),
        ...value,
        ...value.slice(0, offset)
      ])
    }
  },[elementVisible])


  function firstSliderItem() {
    if (infinite) {
      return offset + 1
    } else return 0
  }

  return [
    elements,
    setElements,
    currentIndex,
    setCurrentIndex
  ]
}
