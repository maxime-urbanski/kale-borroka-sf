<?php

namespace App\Service;

use App\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ArticleRepository $articleRepository
    ) {
    }

    public function addToCart(int $id): void
    {
        $cart = $this->getSession()->get('cart', []);

        if (!empty($cart[$id])) {
            ++$cart[$id];
        } else {
            $cart[$id] = 1;
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

    public function getFullCart(): array
    {
        $cart = $this->getSession()->get('cart', []);
        $cartWithData = [];

        foreach ($cart as $id => $quantity) {
            $cartWithData[] = [
                'product' => $this->articleRepository->find($id),
                'quantity' => $quantity,
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
