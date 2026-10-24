<?php

declare(strict_types=1);

namespace App\Account;

use App\Entity\Article;
use App\Entity\Order;
use App\Entity\User;

interface OrderArticlesInterface
{
    /**
     * @return list<Article>
     */
    public function inStock(Order $order): array;

    /**
     * @return list<Article>
     */
    public function notCollectedBy(Order $order, User $user): array;
}
