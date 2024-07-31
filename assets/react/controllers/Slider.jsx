import React, {useEffect, useRef, useState} from "react";
import {useElementTransition} from "./hook/useElementTransition";
import useInfinite from "./hook/useInfinite";


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
                    options = {
                        infinite: false,
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
                        arrow: true,
                        loop: false
                    }
                }) => {

    const [elementVisible, elementToScroll] = useElementTransition(options)
    const [
        elements,
        setElements,
        currentIndex,
        setCurrentIndex
    ] = useInfinite(products, options)
    const sliderContainerRef = useRef(null)
    const ratio = elementVisible / elementToScroll
    const widthSliderItem = 100 / elementVisible + '%'
    const translate = (100 / ratio) * currentIndex + '%'
    const dotArrElement = []
    const disabled = Math.ceil((elements.length - elementVisible) / elementToScroll);

    const prev = () => {
        goToSlide(currentIndex - 1)
    }

    const next = () => {
        goToSlide(currentIndex + 1)
    }

    const goToSlide = (index, animation = true) => {
        if (index < 0) {
            index = elements.length - elementVisible
        } else if (index > disabled) {
            index = 0
        }
        setCurrentIndex(value => value = index)
    }

    if (options.dot) {
        for (let indexLoop = 0; indexLoop <= disabled; indexLoop++) {
            const className = currentIndex === indexLoop ? 'dot active' : 'dot'
            dotArrElement.push(
                <Dot className={className}
                     key={indexLoop}
                     changeIndex={() => goToSlide(indexLoop)}
                />
            )
        }
    }

    return (
        <div className="d-flex flex-column">
            <div className="slider">
                {options.arrow &&
                    <ButtonAction position={'top-50 start-0'}
                                  text={<i className="bi bi-chevron-left"></i>}
                                  action={prev}
                                  disabled={!options.loop && currentIndex === 0}
                    />
                }
                <div className="slider-container"
                     ref={sliderContainerRef}
                     style={{transform: `translateX(-${translate})`}}
                >
                    {elements.map((product, indexMap) => (
                        <article className="slider-item"
                                 style={{'--width': widthSliderItem}} key={indexMap}>
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
                        </article>
                    ))}
                </div>
                {options.arrow &&
                    <ButtonAction position={'top-50 end-0'}
                                  text={<i className="bi bi-chevron-right"></i>}
                                  action={next}
                                  disabled={!options.loop && currentIndex >= disabled}
                    />
                }
                {options.dot &&
                    <div className="d-flex justify-content-center mt-3">
                        {dotArrElement}
                    </div>
                }
            </div>
        </div>
    )
}

export default Slider
