<?php

declare(strict_types=1);

namespace App\Data;

use App\Entity\Article;
use Symfony\Component\Validator\Constraints as Assert;

class AddToCartWithQuantity
{
    /** CartService also caps it to the stock. */
    #[Assert\Positive]
    public int $quantity = 1;

    public ?int $quantityAvailable = null;

    public function __construct(
        private readonly Article $article,
    ) {
        $this->quantityAvailable = $this->article->getStock();
    }
}
