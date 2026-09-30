<?php

declare(strict_types=1);

namespace App\Controller\User\Wishlist;

use App\Entity\Article;
use App\Entity\User;
use App\Entity\WishlistItem;
use App\Repository\WishlistItemRepository;
use App\Repository\WishlistRepository;
use App\Security\ActionCsrfToken;
use App\Service\RefererInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
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
        methods: [Request::METHOD_POST]
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function __invoke(
        #[CurrentUser]
        User $user,
        #[MapEntity(mapping: ['productId' => 'id'])]
        Article $article,
        RefererInterface $referer,
        WishlistItemRepository $wishlistItemRepository,
        WishlistRepository $wishlistRepository,
        Request $request,
        ActionCsrfToken $actionCsrfToken,
    ): RedirectResponse {
        if (!$actionCsrfToken->isValid($request, ActionCsrfToken::WISHLIST)) {
            return new RedirectResponse($referer->getReferer());
        }

        // Drafts are invisible in the shop: do not let them in through a guessed id either.
        if (!$article->isPublished()) {
            throw new NotFoundHttpException();
        }

        /** @var Session $session */
        $session = $request->getSession();

        $wishlist = $wishlistRepository->findOneBy(['user' => $user]);

        // Already added from another tab: the composite key would refuse a second row.
        if (null !== $wishlistItemRepository->findOneBy(['wishlist' => $wishlist, 'article' => $article])) {
            $session->getFlashBag()->add('danger', 'Cet article est déjà dans ta wantlist.');

            return new RedirectResponse($referer->getReferer());
        }

        $wishlistItem = new WishlistItem();
        $wishlistItem->setWishlist($wishlist);
        $wishlistItem->setArticle($article);
        $wishlistItem->setAddedAt(
            new \DateTimeImmutable('now',
                new \DateTimeZone('Europe/Paris'))
        );

        $wishlistItemRepository->save($wishlistItem, true);

        $session->getFlashBag()->add(
            'success',
            $article->getName().' à bien été ajouté à la wantlist'
        );

        return new RedirectResponse($referer->getReferer());
    }
}
