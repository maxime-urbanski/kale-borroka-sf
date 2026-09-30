<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class CartService implements CartInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private ArticleRepository $articleRepository,
    ) {
    }

    /**
     * Adds at least one item, never more than the stock: the quantity comes from the
     * visitor and can be anything (0, negative, above the stock).
     */
    public function addToCart(int $articleId, int $quantity = 1): int
    {
        $cart = $this->getSession()->get('cart', []);
        $article = $this->articleRepository->find($articleId);

        if (!$article?->isPublished()) {
            throw new NotFoundHttpException('Ooups une erreur est survenue.');
        }

        $inCart = min(($cart[$articleId] ?? 0) + max(1, $quantity), max(0, (int) $article->getStock()));

        if ($inCart > 0) {
            $cart[$articleId] = $inCart;
        } else {
            unset($cart[$articleId]);
        }

        $this->getSession()->set('cart', $cart);

        return $inCart;
    }

    public function addQuantity(int $id): int
    {
        return $this->addToCart($id);
    }

    public function quantityOf(int $id): int
    {
        return (int) ($this->getSession()->get('cart', [])[$id] ?? 0);
    }

    public function removeQuantity(int $id): bool
    {
        $cart = $this->getSession()->get('cart', []);

        if (!isset($cart[$id])) {
            return false;
        }

        --$cart[$id];

        if ($cart[$id] <= 0) {
            unset($cart[$id]);
        }

        $this->getSession()->set('cart', $cart);

        return true;
    }

    public function removeToCart(int $id): bool
    {
        $cart = $this->getSession()->get('cart', []);

        if (!isset($cart[$id])) {
            return false;
        }

        unset($cart[$id]);
        $this->getSession()->set('cart', $cart);

        return true;
    }

    public function removeAll(): void
    {
        $this->getSession()->set('cart', []);
    }

    /**
     * @return array<int, array{product: Article, quantity: int, quantityMaxAvailable: int}>
     */
    public function getFullCart(): array
    {
        $cart = $this->getSession()->get('cart', []);
        $cartWithData = [];
        foreach ($cart as $id => $quantity) {
            $article = $this->articleRepository->find($id);

            // Deleted or unpublished since it was added: drop it from the cart.
            if (!$article?->isPublished()) {
                unset($cart[$id]);
                $this->getSession()->set('cart', $cart);

                continue;
            }

            // Sold since it was added: never more than the stock, and no empty line.
            $available = min($quantity, max(0, (int) $article->getStock()));

            if ($available <= 0) {
                unset($cart[$id]);
                $this->getSession()->set('cart', $cart);

                continue;
            }

            if ($available !== $quantity) {
                $cart[$id] = $available;
                $this->getSession()->set('cart', $cart);
            }

            $cartWithData[] = [
                'product' => $article,
                'quantity' => $available,
                'quantityMaxAvailable' => $article->getStock(),
            ];
        }

        return $cartWithData;
    }

    public function getTotal(): int
    {
        $totalPrice = 0;
        $cart = $this->getFullCart();

        foreach ($cart as $item) {
            $totalItem = $item['product']->getPrice() * $item['quantity'];
            $totalPrice += $totalItem;
        }

        return $totalPrice;
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
