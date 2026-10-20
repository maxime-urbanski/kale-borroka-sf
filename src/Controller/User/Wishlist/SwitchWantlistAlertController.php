<?php

declare(strict_types=1);

namespace App\Controller\User\Wishlist;

use App\Catalog\RecordNames;
use App\Entity\User;
use App\Repository\WishlistItemRepository;
use App\Repository\WishlistRepository;
use App\Security\ActionCsrfToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The e-mail alert switch of a wantlist record (maquette 11): on ↔ off. Only a record of the
 * user's own wantlist, looked up through it.
 */
#[AsController]
class SwitchWantlistAlertController
{
    #[Route(
        path: '/wishlist/alert/{productId}',
        name: 'app_wishlist_alert',
        requirements: ['productId' => Requirement::DIGITS],
        methods: [Request::METHOD_POST]
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function __invoke(
        #[CurrentUser]
        User $user,
        int $productId,
        Request $request,
        ActionCsrfToken $actionCsrfToken,
        WishlistRepository $wishlistRepository,
        WishlistItemRepository $wishlistItemRepository,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
    ): RedirectResponse {
        $redirect = new RedirectResponse($urlGenerator->generate('app_user_wishlist'), Response::HTTP_SEE_OTHER);

        if (!$actionCsrfToken->isValid($request, ActionCsrfToken::WISHLIST)) {
            return $redirect;
        }

        /** @var Session $session */
        $session = $request->getSession();

        $wishlist = $wishlistRepository->findOneBy(['user' => $user]);
        $item = null === $wishlist ? null : $wishlistItemRepository->findOneBy(['wishlist' => $wishlist, 'article' => $productId]);
        $article = $item?->getArticle();

        // Removed from another tab, or not this user's.
        if (null === $item || null === $article) {
            $session->getFlashBag()->add('danger', 'Cet article n\'est pas dans ta wantlist.');

            return $redirect;
        }

        $item->setAlertEnabled(!$item->isAlertEnabled());
        $entityManager->flush();

        $names = RecordNames::of($article);
        $session->getFlashBag()->add('success', \sprintf(
            '%s pour %s.',
            $item->isAlertEnabled() ? 'Alerte activée' : 'Alerte coupée',
            ($names['artist'] ? $names['artist'].', ' : '').$names['title'],
        ));

        return $redirect;
    }
}
