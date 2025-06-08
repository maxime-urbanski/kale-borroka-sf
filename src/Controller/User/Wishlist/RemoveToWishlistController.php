<?php

declare(strict_types=1);

namespace App\Controller\User\Wishlist;

use App\Entity\Article;
use App\Entity\User;
use App\Repository\WishlistItemRepository;
use App\Repository\WishlistRepository;
use App\Service\RefererInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
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
        methods: [Request::METHOD_GET]
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
    ): RedirectResponse {
        /** @var Session $session */
        $session = $request->getSession();

        try {
            $userWishlist = $wishlistRepository->getUserWishlist($user);

            $wishlistItem = $wishlistItemRepository->findOneBy([
                'wishlist' => $userWishlist->getOneOrNullResult(),
                'article' => $article,
            ]);

            if (null === $wishlistItem) {
                throw new NotFoundHttpException();
            }

            $wishlistItemRepository->remove($wishlistItem, true);
            $session->getFlashBag()->add(
                'success',
                $article->getName().' à bien été supprimé de la wantlist'
            );
        } catch (NotFoundHttpException $exception) {
            $session->getFlashBag()->add(
                'danger',
                $exception
            );
        }

        return new RedirectResponse($referer->getReferer());
    }
}
