import React, {useEffect, useMemo, useRef, useState} from "react";
import useElementTransition from "./hook/useElementTransition";


const Dot = ({className, changeIndex}) => {
    return <div className={className} onClick={changeIndex}/>
}

const ButtonAction = ({position, disabled, action, text}) => {
    return (
        <button className={`btn btn-outline-dark position-absolute rounded-circle ${position} z-3`}
                onClick={action}
                disabled={disabled}
        >
            {text}
        </button>
    )
}

const Slider = ({
                    products,
                    option = {
                        infinite: true,
                        auto: false,
                        breakpoint: {
                            mobile: [576],
                            tablet: [577, 992],
                            computer: [993]
                        },
                        responsive: {
                            mobile: {
                                elementVisible: 1,
                                elementToScroll: 1
                            },
                            tablet: {
                                elementVisible: 2,
                                elementToScroll: 2
                            },
                            computer: {
                                elementVisible: 4,
                                elementToScroll: 1
                            }
                        },
                        dot: true,
                        arrow: true
                    }
                }) => {

    const parseProducts = JSON.parse(products)
    const sliderContainerRef = useRef(null)
    const [elements, setElements] = useState(parseProducts)
    const [index, setIndex] = useState(0)
    const indexMemo = useMemo(() => index, [index])
    const {elementVisible, elementToScroll} = useElementTransition(option)
    const prev = () => {
        setIndex(value => value -= 1)
    }
    const next = () => {
        setIndex(value => value += 1)
    }

    const ratio = elementVisible / elementToScroll
    const widthSliderItem = 100 / elementVisible
    const translate = (100 / ratio) * indexMemo + '%'
    const dotArrElement = []
    const disabled = Math.ceil((parseProducts.length - elementVisible) / elementToScroll)
    const disabledMemo = useMemo(() => disabled, [disabled])

    if (option.dot) {
        const changeIndex = (indexDot) => setIndex(indexDot)
        for (let indexLoop = 0; indexLoop <= disabledMemo; indexLoop++) {
            const className = index === indexLoop ? 'dot active' : 'dot'
            dotArrElement.push(
                <Dot className={className}
                     key={indexLoop}
                     changeIndex={() => changeIndex(indexLoop)}
                />
            )
        }
    }

    if (option.infinite) {
        option.dot = false
        const firstElementVisibleDuplicate = parseProducts.slice(0, elementVisible)
        const lastElementVisibleDuplicate = parseProducts.slice(-elementVisible)
        const infiniteArray = [...lastElementVisibleDuplicate, ...elements, ...firstElementVisibleDuplicate]

        useEffect(() => {
            setElements(infiniteArray)
            setIndex(elementVisible)
            const handleTransitionEnd = () => {
                if (index >= elements.length + elementVisible) {
                    setIndex(elementVisible)
                    sliderContainerRef.current.style.transition = 'none'
                } else if (index <= 0 ) {
                    setIndex(elements.length)
                }

                sliderContainerRef.current.addEventListener('transitionend', handleTransitionEnd)

                return () => sliderContainerRef.current.removeEventListener('transitionend', handleTransitionEnd)
            }
        }, [index, elements.length, elementVisible]);
    }

    return (
        <div className="d-flex flex-column">
            <div className="slider py-5">
                {option.arrow &&
                    <ButtonAction position={'top-50 start-0'}
                                  text={<i className="bi bi-chevron-left"></i>}
                                  action={prev}
                    />
                }
                <div className="slider-container"
                     ref={sliderContainerRef}
                     style={{transform: `translateX(-${translate})`}}
                >
                    {elements.map((product, indexMap) => (
                        <div className="slider-item"
                             style={{width: widthSliderItem + '%'}} key={indexMap}>
                            <div className="card">
                                <img src="/images/product_placeholder.png" alt="{product.name}"/>
                                <div className="card-body">
                                    <a href={`/catalog/${product.support.name}/${product?.slug}`}
                                       className="card-title text-center w-100">
                                        <p>{product.name}</p>
                                    </a>
                                    <div className="d-flex justify-content-between align-items-center mt-3">
                                        <p className="mt-3">Prix: {product?.price / 100}€</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
                {option.arrow &&
                    <ButtonAction position={'top-50 end-0'}
                                  text={<i className="bi bi-chevron-right"></i>}
                                  action={next}
                    />
                }
                {option.dot &&
                    <div className="d-flex justify-content-center mt-3">
                        {dotArrElement}
                    </div>
                }
            </div>
        </div>
    )
}

export default Slider
