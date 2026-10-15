<?php

declare(strict_types=1);

namespace App\Controller\User\Account;

use App\Account\AccountCountsInterface;
use App\Entity\Article;
use App\Entity\User;
use App\Entity\UserCollectionItems;
use App\Entity\WishlistItem;
use App\Repository\AddressRepository;
use App\Repository\ArticleRepository;
use App\Repository\OrderRepository;
use App\Repository\UserCollectionItemsRepository;
use App\Repository\WishlistItemRepository;
use Doctrine\ORM\NonUniqueResultException;
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
 * Mon compte, tableau de bord (maquette 07). The route keeps its historical name.
 */
#[AsController]
class AccountDashboardController
{
    /** Orders in the table; the mobile list shows the first three. */
    private const int ORDERS = 4;
    /** Covers per crate. */
    private const int COVERS = 4;

    /**
     * @throws SyntaxError
     * @throws NonUniqueResultException
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/mon-compte',
        name: 'app_user_informations',
        methods: [Request::METHOD_GET]
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Environment $twig,
        AccountCountsInterface $accountCounts,
        OrderRepository $orderRepository,
        WishlistItemRepository $wishlistItemRepository,
        UserCollectionItemsRepository $userCollectionItemsRepository,
        AddressRepository $addressRepository,
        ArticleRepository $articleRepository,
    ): Response {
        $wantlist = $wishlistItemRepository->latestOfUser($user, self::COVERS);
        $collection = $userCollectionItemsRepository->latestOfUser($user, self::COVERS);
        $articleRepository->preloadCovers(array_values(array_filter(array_map(
            static fn (WishlistItem|UserCollectionItems $item): ?Article => $item->getArticle(),
            [...$wantlist, ...$collection],
        ))));

        return new Response($twig->render('user/dashboard.html.twig', [
            'user' => $user,
            'counts' => $accountCounts->of($user),
            'orders' => $orderRepository->latestOfBuyer($user, self::ORDERS),
            'wantlist' => $wantlist,
            'collection' => $collection,
            'covers' => self::COVERS,
            'defaultAddress' => $addressRepository->getDefaultUserAddress($user),
        ]));
    }
}
