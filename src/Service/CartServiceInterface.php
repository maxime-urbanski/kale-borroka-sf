<?php

namespace App\Service;

use App\Entity\Article;

interface CartServiceInterface
{
    public function addToCart(int $id): void;

    public function addQuantity(int $id): void;

    public function removeQuantity(int $id): void;

    public function removeToCart(int $id): void;

    public function removeAll(): void;

    /**
     * @return array<int, array{product: Article, quantity: int, quantityMaxAvailable: int}>
     */
    public function getFullCart(): array;

    public function getTotal(): int;
}
