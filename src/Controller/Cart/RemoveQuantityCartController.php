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
class RemoveQuantityCartController
{
    #[Route(
        path: '/cart/remove_quantity/{id}',
        name: 'app_cart_remove_quantity',
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
            $cart->removeQuantity($id);
            $session->getFlashBag()->add('success', 'Quantité mise à jour');
        } catch (NotFoundHttpException $exception) {
            $session->getFlashBag()->add('danger', $exception);
        }

        return new RedirectResponse($referer->getReferer());
    }
}
