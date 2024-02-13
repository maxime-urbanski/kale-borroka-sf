<?php

declare(strict_types=1);

namespace App\Controller\Cart;

use App\Service\CartInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
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
        CartInterface $cartInterface
    ): Response {
        $content = $twig->render('cart/index.html.twig', [
            'cart' => $cartInterface->getFullCart(),
            'total' => $cartInterface->getTotal(),
        ]);

        return new Response($content);
    }
}
