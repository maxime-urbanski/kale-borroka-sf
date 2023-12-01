import React, {useMemo, useState} from "react";
import Card from "./Card";

function Carousel({products, elementVisible = 1, elementToScroll = 1}) {
    const [index, setIndex] = useState(0)
    const [array, setArray] = useState(products)
    const indexMemo = useMemo(() => index, [index])
    const maxIndexValue = array.length % elementToScroll
    const next = () => {
        let infinite = [...array, ...array.slice(0, elementToScroll)].slice(-array.length)
        setArray(infinite)
        setIndex(index + 1)
        if (index >= array.length - elementVisible ) setIndex(0)
    }


    const prev = () => {
        let infinite = [...array.slice(-elementToScroll), ...array].slice(0, array.length)
        setArray(infinite)
        setIndex(index - 1)
        if (index < 0) setIndex(array.length - 1)
    }

    const ratio = elementVisible / elementToScroll
    const widthCarouselItem = 100 / elementVisible + '%'
    const translate = (100 / ratio) * indexMemo + '%'

    // TODO: Fix Translate

    return (
        <div className="d-flex flex-column mt-5">
            <div className="custom-carousel">
                <button onClick={prev}
                        className='btn btn-outline-dark position-absolute top-50 start-0 z-3'>
                    <i className="bi bi-caret-left-fill"></i>
                </button>
                <div className="custom-carousel-container">
                    {array.map((product, index) => {
                            return <div
                                className="custom-carousel-item"
                                style={{width: widthCarouselItem}}
                                key={index}>
                                <Card product={product}/>
                            </div>
                        }
                    )}
                </div>
                <button onClick={next} className='btn btn-outline-dark position-absolute top-50 end-0'>
                    <i className="bi bi-caret-right-fill"></i>
                </button>

            </div>
        </div>
    )
}

export default Carousel
