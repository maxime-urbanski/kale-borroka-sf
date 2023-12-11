<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

readonly class CartService
{
    public function __construct(
        private RequestStack $requestStack,
        private ArticleRepository $articleRepository
    ) {
    }

    public function addToCart(int $id): void
    {
        $cart = $this->getSession()->get('cart', []);

        if (empty($cart[$id])) {
            $cart[$id] = 1;
        } elseif ($this->articleRepository->find($id)->getQuantity() > $cart[$id]) {
            ++$cart[$id];
        } else {
            $cart[$id] = $this->articleRepository->find($id)->getQuantity();
        }

        $this->getSession()->set('cart', $cart);
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
