<?php

declare(strict_types=1);

namespace App\Controller\User\Wishlist;

use App\Entity\Article;
use App\Entity\User;
use App\Entity\Wishlist;
use App\Entity\WishlistItem;
use App\Repository\WishlistItemRepository;
use App\Repository\WishlistRepository;
use App\Security\ActionCsrfToken;
use App\Service\RefererInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
        UrlGeneratorInterface $urlGenerator,
    ): RedirectResponse {
        if (!$actionCsrfToken->isValid($request, ActionCsrfToken::WISHLIST)) {
            return new RedirectResponse($referer->getReferer());
        }

        /** @var Session $session */
        $session = $request->getSession();

        // Unpublished since the page was shown, or a draft's guessed id: nothing to add.
        // Not back to the article page, which is a 404 now.
        if (!$article->isPublished()) {
            $session->getFlashBag()->add('danger', 'Cet article n\'est plus disponible.');

            return new RedirectResponse($urlGenerator->generate('app_catalog'));
        }

        // Accounts created before the lists existed have no wishlist yet.
        $wishlist = $wishlistRepository->findOneBy(['user' => $user]);
        if (null === $wishlist) {
            $wishlist = (new Wishlist())->setUser($user);
            $wishlistRepository->save($wishlist);
        }

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

        try {
            $wishlistItemRepository->save($wishlistItem, true);
        } catch (UniqueConstraintViolationException) {
            // Double click: the other request added it between the check and this insert.
            $session->getFlashBag()->add('danger', 'Cet article est déjà dans ta wantlist.');

            return new RedirectResponse($referer->getReferer());
        }

        $session->getFlashBag()->add(
            'success',
            $article->getName().' a bien été ajouté à la wantlist'
        );

        return new RedirectResponse($referer->getReferer());
    }
}
