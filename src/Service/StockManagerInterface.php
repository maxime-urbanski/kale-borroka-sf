<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Article;
use App\Order\Exception\InsufficientStockException;

interface StockManagerInterface
{
    /**
     * Takes items out of stock, atomically: never lets the stock go below zero, even with
     * two customers paying for the last copy at the same time.
     *
     * @throws InsufficientStockException
     */
    public function take(Article $article, int $quantity): void;

    public function putBack(Article $article, int $quantity): void;
}
