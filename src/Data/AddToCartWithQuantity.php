<?php

declare(strict_types=1);

namespace App\Data;

use App\Entity\Article;

class AddToCartWithQuantity
{
    public int $quantity = 1;

    public ?int $quantityAvailable = null;

    public function __construct(
        private readonly Article $article,
    ) {
        $this->quantityAvailable = $this->article->getQuantity();
    }
}
