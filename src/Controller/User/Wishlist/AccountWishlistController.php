<?php

declare(strict_types=1);

namespace App\Controller\User\Wishlist;

use App\Account\AccountCountsInterface;
use App\Entity\Article;
use App\Entity\User;
use App\Entity\WishlistItem;
use App\Repository\ArticleRepository;
use App\Repository\WishlistItemRepository;
use App\Service\CartInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Wantlist (maquette 11): what can be bought now, then what is on watch.
 */
#[AsController]
class AccountWishlistController
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route(
        path: '/mon-compte/wishlist',
        name: 'app_user_wishlist',
        methods: [Request::METHOD_GET]
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Environment $twig,
        AccountCountsInterface $accountCounts,
        WishlistItemRepository $wishlistItemRepository,
        ArticleRepository $articleRepository,
        CartInterface $cart,
    ): Response {
        $items = $wishlistItemRepository->allOfUser($user);
        $articleRepository->preloadCovers(array_values(array_filter(array_map(
            static fn (WishlistItem $item): ?Article => $item->getArticle(),
            $items,
        ))));

        $available = $watched = [];
        $total = 0;
        foreach ($items as $item) {
            $article = $item->getArticle();
            if ($article?->isInStock()) {
                $available[] = $item;
                // What « Tout ajouter au panier » would add: those not in the cart yet.
                if (0 === $cart->quantityOf((int) $article->getId())) {
                    $total += (int) $article->getPrice();
                }
            } else {
                $watched[] = $item;
            }
        }

        return new Response($twig->render('user/wantlist.html.twig', [
            'counts' => $accountCounts->of($user),
            'available' => $available,
            'watched' => $watched,
            'toAddTotal' => $total,
            'alerts' => \count(array_filter($items, static fn (WishlistItem $item): bool => $item->isAlertEnabled())),
        ]));
    }
}
