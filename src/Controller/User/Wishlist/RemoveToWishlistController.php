<?php

declare(strict_types=1);

namespace App\Controller\User\Wishlist;

use App\Entity\Article;
use App\Entity\User;
use App\Repository\WishlistItemRepository;
use App\Repository\WishlistRepository;
use App\Security\ActionCsrfToken;
use App\Service\RefererInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class RemoveToWishlistController
{
    /**
     * @throws NonUniqueResultException
     */
    #[Route(
        path: '/wishlist/remove/{productId}',
        name: 'app_wishlist_remove',
        requirements: ['productId' => Requirement::DIGITS],
        methods: [Request::METHOD_POST]
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function __invoke(
        #[CurrentUser]
        User $user,
        #[MapEntity(mapping: ['productId' => 'id'])]
        Article $article,
        RefererInterface $referer,
        WishlistRepository $wishlistRepository,
        WishlistItemRepository $wishlistItemRepository,
        Request $request,
        ActionCsrfToken $actionCsrfToken,
    ): RedirectResponse {
        if (!$actionCsrfToken->isValid($request, ActionCsrfToken::WISHLIST)) {
            return new RedirectResponse($referer->getReferer());
        }

        /** @var Session $session */
        $session = $request->getSession();

        $wishlistItem = $wishlistItemRepository->findOneBy([
            'wishlist' => $wishlistRepository->getUserWishlist($user)->getOneOrNullResult(),
            'article' => $article,
        ]);

        // Already removed from another tab.
        if (null === $wishlistItem) {
            $session->getFlashBag()->add('danger', 'Cet article n\'est pas dans ta wantlist.');

            return new RedirectResponse($referer->getReferer());
        }

        $wishlistItemRepository->remove($wishlistItem, true);
        $session->getFlashBag()->add(
            'success',
            $article->getName().' a bien été supprimé de la wantlist'
        );

        return new RedirectResponse($referer->getReferer());
    }
}
