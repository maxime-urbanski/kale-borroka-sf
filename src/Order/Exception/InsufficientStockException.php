<?php

declare(strict_types=1);

namespace App\Order\Exception;

use App\Entity\Article;

class InsufficientStockException extends \DomainException
{
    public function __construct(
        public readonly Article $article,
        public readonly int $requested,
    ) {
        parent::__construct(\sprintf(
            'Stock insuffisant pour « %s » : %d demandé(s), %d disponible(s).',
            $article->getName(),
            $requested,
            $article->getStock() ?? 0,
        ));
    }
}
