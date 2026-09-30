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
class RemoveQuantityCartController
{
    #[Route(
        path: '/cart/remove_quantity/{id}',
        name: 'app_cart_remove_quantity',
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

        $cart->removeQuantity($id);
        $session->getFlashBag()->add('success', 'Quantité mise à jour');

        return new RedirectResponse($referer->getReferer());
    }
}
