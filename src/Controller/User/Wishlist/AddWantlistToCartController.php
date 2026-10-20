<?php

declare(strict_types=1);

namespace App\Controller\User\Wishlist;

use App\Entity\User;
use App\Repository\WishlistItemRepository;
use App\Security\ActionCsrfToken;
use App\Service\CartInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * « Tout ajouter au panier » (maquette 11): one copy of every wantlist record on sale, those
 * already in the cart left as they are. The wantlist keeps them all.
 */
#[AsController]
class AddWantlistToCartController
{
    #[Route(
        path: '/wishlist/add-all-to-cart',
        name: 'app_wishlist_add_all_to_cart',
        methods: [Request::METHOD_POST]
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Request $request,
        ActionCsrfToken $actionCsrfToken,
        WishlistItemRepository $wishlistItemRepository,
        CartInterface $cart,
        UrlGeneratorInterface $urlGenerator,
    ): RedirectResponse {
        $redirect = new RedirectResponse($urlGenerator->generate('app_user_wishlist'), Response::HTTP_SEE_OTHER);

        if (!$actionCsrfToken->isValid($request, ActionCsrfToken::CART)) {
            return $redirect;
        }

        $added = 0;
        foreach ($wishlistItemRepository->allOfUser($user) as $item) {
            $article = $item->getArticle();

            if (null !== $article?->getId() && $article->isInStock() && 0 === $cart->quantityOf($article->getId())) {
                if ($cart->addToCart($article->getId(), 1) > 0) {
                    ++$added;
                }
            }
        }

        /** @var Session $session */
        $session = $request->getSession();
        $session->getFlashBag()->add(
            $added > 0 ? 'success' : 'info',
            match ($added) {
                0 => 'Tout ce qui est dispo est déjà dans ton panier.',
                1 => '1 article ajouté au panier.',
                default => $added.' articles ajoutés au panier.',
            },
        );

        return $redirect;
    }
}
