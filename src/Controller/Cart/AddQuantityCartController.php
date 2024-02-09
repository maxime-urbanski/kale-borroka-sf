<?php

declare(strict_types=1);

namespace App\Controller\Cart;

use App\Service\CartInterface;
use App\Service\RefererInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[asController]
class AddQuantityCartController
{
    #[Route(
        path: '/cart/add_quantity/{id}',
        name: 'app_cart_add_quantity',
        requirements: ['id' => Requirement::DIGITS],
        methods: [Request::METHOD_GET]
    )]
    public function __invoke(
        SessionInterface $session,
        CartInterface $cart,
        RefererInterface $referer,
        int $id
    ): RedirectResponse {
        try {
            $cart->addQuantity($id);
            $session->getFlashBag()->add('success', 'Quantité mise à jour');
        } catch (NotFoundHttpException $exception) {
            $session->getFlashBag()->add('danger', $exception);
        }

        return new RedirectResponse($referer->getReferer());
    }
}
