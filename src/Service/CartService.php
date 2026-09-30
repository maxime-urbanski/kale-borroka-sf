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

        $inCart = (int) min(($cart[$articleId] ?? 0) + max(1, $quantity), max(0, (int) $article->getStock()));

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
     * Sum of the quantities kept in the session, for the navbar: no query. Lines dropped or
     * capped by getFullCart() (unpublished, sold out) are only corrected once the cart or
     * delivery page is shown.
     */
    public function countItems(): int
    {
        return array_sum(array_map(intval(...), $this->getSession()->get('cart', [])));
    }

    /**
     * @return array<int, array{product: Article, quantity: int, quantityMaxAvailable: int}>
     */
    public function getFullCart(): array
    {
        $cart = $this->getSession()->get('cart', []);

        if ([] === $cart) {
            return [];
        }

        $articles = [];
        foreach ($this->articleRepository->findForCart(array_map(intval(...), array_keys($cart))) as $article) {
            $articles[(int) $article->getId()] = $article;
        }

        $cartWithData = [];
        $changed = false;

        foreach ($cart as $id => $quantity) {
            $article = $articles[$id] ?? null;
            // Deleted, unpublished or sold since it was added: never more than the stock,
            // and no empty line.
            $available = $article?->isPublished() ? min($quantity, max(0, (int) $article->getStock())) : 0;

            if ($available !== $quantity) {
                $changed = true;
            }

            if (null === $article || $available <= 0) {
                unset($cart[$id]);

                continue;
            }

            $cart[$id] = $available;
            $cartWithData[] = [
                'product' => $article,
                'quantity' => $available,
                'quantityMaxAvailable' => (int) $article->getStock(),
            ];
        }

        if ($changed) {
            $this->getSession()->set('cart', $cart);
        }

        return $cartWithData;
    }

    /**
     * @param array<int, array{product: Article, quantity: int, quantityMaxAvailable: int}> $fullCart the lines of getFullCart(), whose prices come from the database
     */
    public function getTotal(array $fullCart): int
    {
        $totalPrice = 0;

        foreach ($fullCart as $item) {
            $totalPrice += (int) $item['product']->getPrice() * $item['quantity'];
        }

        return $totalPrice;
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
