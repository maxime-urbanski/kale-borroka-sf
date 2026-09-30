<?php

declare(strict_types=1);

namespace App\Controller\Cart;

use App\Security\ActionCsrfToken;
use App\Service\CartInterface;
use App\Service\RefererInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[AsController]
class RemoveToCartController
{
    #[Route(
        path: '/cart/remove/{id}',
        name: 'app_cart_remove',
        requirements: ['id' => Requirement::DIGITS],
        methods: [Request::METHOD_POST]
    )]
    public function __invoke(
        Request $request,
        CartInterface $cart,
        RefererInterface $referer,
        int $id,
        ActionCsrfToken $actionCsrfToken,
    ): RedirectResponse {
        if (!$actionCsrfToken->isValid($request, ActionCsrfToken::CART)) {
            return new RedirectResponse($referer->getReferer());
        }

        /** @var Session $session */
        $session = $request->getSession();
        // Already removed from another tab.
        $cart->removeToCart($id)
            ? $session->getFlashBag()->add('success', 'Article retiré du panier.')
            : $session->getFlashBag()->add('danger', 'Cet article n\'est plus dans ton panier.');

        return new RedirectResponse($referer->getReferer());
    }
}
