<?php

declare(strict_types=1);

namespace App\Controller\Cart;

use App\Service\CartInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

#[AsController]
class EmptyCartController
{
    #[Route(
        path: '/cart/empty_cart',
        name: 'app_cart_empty',
        methods: [Request::METHOD_GET]
    )]
    public function __invoke(
        CartInterface $cart,
        RouterInterface $router
    ): RedirectResponse {
        $cart->removeAll();

        return new RedirectResponse($router->generate('app_cart_index'));
    }
}
