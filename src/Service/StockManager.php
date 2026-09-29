<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Article;
use App\Order\Exception\InsufficientStockException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Stock changes are single conditional UPDATEs rather than read-modify-write on the
 * entity, so concurrent orders cannot oversell. The entity is refreshed afterwards so
 * that a later flush does not write a stale value back.
 */
readonly class StockManager implements StockManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function take(Article $article, int $quantity): void
    {
        $updated = $this->entityManager->getConnection()->executeStatement(
            'UPDATE article SET stock = stock - :quantity WHERE id = :id AND stock >= :quantity',
            ['quantity' => $quantity, 'id' => $article->getId()],
        );

        if (0 === $updated) {
            throw new InsufficientStockException($article, $quantity);
        }

        $this->entityManager->refresh($article);
    }

    public function putBack(Article $article, int $quantity): void
    {
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE article SET stock = stock + :quantity WHERE id = :id',
            ['quantity' => $quantity, 'id' => $article->getId()],
        );

        $this->entityManager->refresh($article);
    }
}
