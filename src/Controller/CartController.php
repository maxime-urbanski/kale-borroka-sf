<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CartService;
use App\Service\RedirectRefererService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cart', name: 'app_cart_')]
class CartController extends AbstractController
{
    #[Route('/', 'index')]
    public function index(CartService $cartService): Response
    {
        $cart = $cartService->getFullCart();
        $total = $cartService->getTotal();

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => $total,
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/add/{id}', 'add')]
    public function addToCart(
        CartService            $cartService,
        RedirectRefererService $redirectReferer,
        Request                $request,
        int                    $id
    ): ?Response
    {
        try {
            $cartService->addToCart($id);
            $this->addFlash('success', 'article ajouté au panier');
        } catch (\Exception $e) {
            throw new \Exception($e);
        }

        return $this->redirect($redirectReferer->getRefererRoute($request));
    }

    #[Route('/remove/{id}', 'remove')]
    public function removeToCart(
        CartService $cartService,
        int         $id
    ): Response
    {
        $cartService->removeToCart($id);

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/empty_cart', 'empty')]
    public function emptyCart(
        CartService $cartService
    ): Response
    {
        $cartService->removeAll();

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/add_quantity/{id}', 'add_quantity')]
    public function addQuantity(
        CartService $cartService,
        int         $id
    ): Response
    {
        $cartService->addQuantity($id);

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove_quantity/{id}', 'remove_quantity')]
    public function removeQuantity(
        CartService $cartService,
        int         $id
    ): Response
    {
        $cartService->removeQuantity($id);

        return $this->redirectToRoute('app_cart_index');
    }
}
