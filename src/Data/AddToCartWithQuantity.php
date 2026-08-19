<?php

declare(strict_types=1);

namespace App\Data;

use App\Entity\Article;

class AddToCartWithQuantity
{
    public int $quantity = 1;

    public ?int $quantityAvailable = null;

    /**
     * Offer being bought. The album page renders a single add-to-cart form and lets the
     * edition picker retarget it, so which Article is meant only becomes known on submit.
     */
    public ?int $articleId = null;

    public function __construct(
        private readonly Article $article,
    ) {
        $this->quantityAvailable = $this->article->getQuantity();
        $this->articleId = $this->article->getId();
    }

    public function getArticle(): Article
    {
        return $this->article;
    }
}
