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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[AsController]
class AddToCartController
{
    #[Route(
        path: '/cart/add/{id}',
        name: 'app_cart_add',
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

        try {
            $choosenQuantity = $request->getPayload()->getInt('quantity') ?: 1;

            $cart->addToCart($id, $choosenQuantity);

            $session->getFlashBag()->add('success', 'article ajouté au panier.');
        } catch (NotFoundHttpException) {
            // Unpublished or deleted since the page was shown.
            $session->getFlashBag()->add('danger', 'Cet article n\'est plus disponible.');
        }

        return new RedirectResponse($referer->getReferer());
    }
}
