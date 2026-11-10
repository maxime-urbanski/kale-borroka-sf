<?php

declare(strict_types=1);

namespace App\Controller\Cart;

use App\Service\CartInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
class CartController
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route(
        path: '/cart',
        name: 'app_cart_index',
        methods: [Request::METHOD_GET]
    )]
    public function __invoke(
        Environment $twig,
        CartInterface $cartInterface,
        Request $request,
    ): Response {
        $cart = $cartInterface->getFullCart();
        $content = $twig->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => $cartInterface->getTotal($cart),
            // « Vider le panier » links to ?vider: the confirmation shows above the lines (no JS needed).
            'confirm_empty' => $request->query->has('vider'),
        ]);

        return new Response($content);
    }
}
