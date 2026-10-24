<?php

declare(strict_types=1);

namespace App\Account;

use App\Entity\Article;
use App\Entity\Order;
use App\Entity\User;
use App\Repository\UserCollectionItemsRepository;

/**
 * The articles of a finished order that can go somewhere again (maquette 17): back into the
 * cart (« Remettre ces disques dans le panier ») and, once delivered, into the collection.
 * An article taken off the catalogue goes nowhere: its page is a 404.
 */
readonly class OrderArticles implements OrderArticlesInterface
{
    public function __construct(
        private UserCollectionItemsRepository $userCollectionItemsRepository,
    ) {
    }

    /**
     * Published and in stock, once each, in the order of the lines.
     */
    public function inStock(Order $order): array
    {
        return array_values(array_filter($this->published($order), static fn (Article $article): bool => $article->isInStock()));
    }

    /**
     * Published and not in the user's collection yet.
     */
    public function notCollectedBy(Order $order, User $user): array
    {
        $articles = $this->published($order);
        $collected = $this->userCollectionItemsRepository->collectedAmong($user, $articles);

        return array_values(array_filter($articles, static fn (Article $article): bool => !\in_array($article->getId(), $collected, true)));
    }

    /**
     * @return list<Article>
     */
    private function published(Order $order): array
    {
        $articles = [];
        foreach ($order->getProducts() as $article) {
            if (null !== $article->getId() && $article->isPublished()) {
                $articles[$article->getId()] = $article;
            }
        }

        return array_values($articles);
    }
}
