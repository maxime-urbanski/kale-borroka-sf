import React from "react";

function Card({product}) {

    return (
        <div className="card m-5">
            <img src="/images/product_placeholder.png" alt={product.name}/>
            <div className="card-body">
                <a href={`/catalog/${product.support}/${product.id}`}
                   className="card-title text-center w-100">
                    <p>{product.name}</p>
                </a>
                <div className="d-flex justify-content-between align-items-center mt-3">
                    <p className="mt-3">Prix: <span>{product.price / 100} €</span></p>

                </div>
            </div>
        </div>
    )
}

export default Card
