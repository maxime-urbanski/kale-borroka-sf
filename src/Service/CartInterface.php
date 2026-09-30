<?php

namespace App\Service;

use App\Entity\Article;

interface CartInterface
{
    /**
     * @return int how many are in the cart afterwards, never above the stock (0: sold out)
     */
    public function addToCart(int $articleId, int $quantity): int;

    /**
     * @return int how many are in the cart afterwards
     */
    public function addQuantity(int $id): int;

    public function quantityOf(int $id): int;

    /**
     * @return bool false if the article was not in the cart
     */
    public function removeQuantity(int $id): bool;

    /**
     * @return bool false if the article was not in the cart
     */
    public function removeToCart(int $id): bool;

    public function removeAll(): void;

    /**
     * @return array<int, array{product: Article, quantity: int, quantityMaxAvailable: int}>
     */
    public function getFullCart(): array;

    public function getTotal(): int;
}
