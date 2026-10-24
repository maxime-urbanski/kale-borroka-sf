<?php

declare(strict_types=1);

namespace App\Controller\User\Account;

use App\Account\OrderArticlesInterface;
use App\Entity\Order;
use App\Security\ActionCsrfToken;
use App\Security\Voter\OrderVoter;
use App\Service\CartInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * « Remettre ces disques dans le panier » (maquette 17): the articles of an order still on sale,
 * in the quantities ordered (the cart caps them to the stock). Those already in the cart are left
 * as they are, as the wantlist's « Tout ajouter au panier » does.
 */
#[AsController]
class ReorderController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted(OrderVoter::OWN, subject: 'order')]
    #[Route(
        path: '/mon-compte/mes-commandes/{orderReference}/panier',
        name: 'app_user_order_reorder',
        requirements: [
            'orderReference' => 'KBR-[\w-]+',
        ],
        methods: [Request::METHOD_POST]
    )]
    public function __invoke(
        #[MapEntity(mapping: ['orderReference' => 'reference'])]
        Order $order,
        Request $request,
        ActionCsrfToken $actionCsrfToken,
        OrderArticlesInterface $orderArticles,
        CartInterface $cart,
        UrlGeneratorInterface $urlGenerator,
    ): RedirectResponse {
        if (!$actionCsrfToken->isValid($request, ActionCsrfToken::CART)) {
            return new RedirectResponse($urlGenerator->generate('app_user_order', ['orderReference' => $order->getReference()]), Response::HTTP_SEE_OTHER);
        }

        $ordered = [];
        foreach ($order->getOrderDetails() as $line) {
            $id = $line->getProduct()?->getId();
            if (null !== $id) {
                $ordered[$id] = ($ordered[$id] ?? 0) + (int) $line->getQuantity();
            }
        }

        $added = 0;
        foreach ($orderArticles->inStock($order) as $article) {
            $id = (int) $article->getId();

            if (0 === $cart->quantityOf($id) && $cart->addToCart($id, $ordered[$id] ?? 1) > 0) {
                ++$added;
            }
        }

        /** @var Session $session */
        $session = $request->getSession();
        $session->getFlashBag()->add(
            $added > 0 ? 'success' : 'info',
            match ($added) {
                0 => 'Tout ce qui est dispo est déjà dans ton panier.',
                1 => '1 article remis dans le panier.',
                default => $added.' articles remis dans le panier.',
            },
        );

        return new RedirectResponse($urlGenerator->generate('app_cart_index'), Response::HTTP_SEE_OTHER);
    }
}
