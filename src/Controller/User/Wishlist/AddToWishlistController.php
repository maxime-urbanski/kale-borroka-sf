<?php

declare(strict_types=1);

namespace App\Controller\User\Wishlist;

use App\Entity\Article;
use App\Entity\User;
use App\Entity\WishlistItem;
use App\Repository\WishlistItemRepository;
use App\Repository\WishlistRepository;
use App\Service\RefererInterface;
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
class AddToWishlistController
{
    #[Route(
        path: '/wishlist/add/{productId}',
        name: 'app_wishlist_add',
        requirements: ['productId' => Requirement::DIGITS],
        methods: [Request::METHOD_GET]
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function __invoke(
        #[CurrentUser]
        User                   $user,
        #[MapEntity(mapping: ['productId' => 'id'])]
        Article                $article,
        RefererInterface       $referer,
        WishlistItemRepository $wishlistItemRepository,
        WishlistRepository     $wishlistRepository,
        Request                $request,
    ): RedirectResponse
    {
        /** @var Session $session */
        $session = $request->getSession();

        try {
            $wishlistItem = new WishlistItem();
            $wishlist = $wishlistRepository->findOneBy(['user' => $user]);
            $wishlistItem->setWishlist($wishlist);
            $wishlistItem->setArticle($article);
            $wishlistItem->setAddedAt(
                new \DateTimeImmutable('now',
                    new \DateTimeZone('Europe/Paris'))
            );

            $wishlistItemRepository->save($wishlistItem, true);

            $session->getFlashBag()->add(
                'success',
                $article->getName() . ' à bien été ajouté à la wantlist'
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
