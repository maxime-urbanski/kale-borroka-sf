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

    public function addToCart(int $articleId, int $quantity = 1): void
    {
        $cart = $this->getSession()->get('cart', []);
        $article = $this->articleRepository->find($articleId);

        if ($article) {
            if (empty($cart[$articleId])) {
                $cart[$articleId] = $quantity;
            } elseif ($article->getQuantity() > $cart[$articleId]) {
                ++$cart[$articleId];
            } else {
                $cart[$articleId] = $article->getQuantity();
            }

            $this->getSession()->set('cart', $cart);
        } else {
            throw new NotFoundHttpException('Ooups une erreur est survenue.');
        }
    }

    public function addQuantity(int $id): void
    {
        $this->addToCart($id);
    }

    public function removeQuantity(int $id): void
    {
        $cart = $this->getSession()->get('cart', []);
        --$cart[$id];

        if (0 === $cart[$id]) {
            unset($cart[$id]);
        }

        $this->getSession()->set('cart', $cart);
    }

    public function removeToCart(int $id): void
    {
        $cart = $this->getSession()->get('cart', []);

        if (!empty($cart[$id])) {
            unset($cart[$id]);
        }

        $this->getSession()->set('cart', $cart);
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
            $cartWithData[] = [
                'product' => $article,
                'quantity' => $quantity,
                'quantityMaxAvailable' => $article->getQuantity(),
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
