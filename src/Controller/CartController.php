<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    #[Route('/cart', 'app_cart_index')]
    public function index(CartService $cartService): Response
    {
        $cart = $cartService->getFullCart();
        $total = $cartService->getTotal();
        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => $total
        ]);
    }

    #[Route('/cart/add/{id}', 'app_cart_add')]
    public function addToCart(
        CartService $cartService,
        int         $id
    ): Response
    {
        $cartService->addToCart($id);

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/cart/remove/{id}', 'app_cart_remove')]
    public function removeToCart(
        CartService $cartService,
        int         $id
    ): Response
    {
        $cartService->removeToCart($id);

        return $this->redirectToRoute('app_cart_index');
    }
}
