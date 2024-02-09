<?php

declare(strict_types=1);

namespace App\Controller\Cart;

use App\Service\CartInterface;
use App\Service\RefererInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[asController]
class AddToCartController
{
    #[Route(
        path: '/cart/add/{id}',
        name: 'app_cart_add',
        requirements: ['id' => Requirement::DIGITS],
        methods: [Request::METHOD_GET]
    )]
    public function __invoke(
        Request $request,
        CartInterface $cart,
        RefererInterface $referer,
        int $id
    ): RedirectResponse {
        $session = $request->getSession();
        try {
            $cart->addToCart($id);
            $session->getFlashBag()->add('success', 'article ajouté au panier.');
        } catch (NotFoundHttpException $exception) {
            $session->getFlashBag()->add('error', $exception);
        }

        return new RedirectResponse($referer->getReferer());
    }
}
