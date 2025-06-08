<?php

declare(strict_types=1);

namespace App\Controller\User\Wishlist;

use App\Entity\User;
use App\Repository\WishlistRepository;
use App\Service\CustomPaginationInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
class AccountWishlistController extends AbstractController
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     * @throws NonUniqueResultException
     */
    #[Route(
        path: '/mon-compte/wishlist/{page}',
        name: 'app_user_wishlist',
        requirements: [
            'page' => '^(page-)' . Requirement::DIGITS,
        ],
        defaults: ['page' => 'page-1'],
        methods: [Request::METHOD_GET]
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function __invoke(
        #[CurrentUser]
        User                      $user,
        Environment               $twig,
        WishlistRepository        $wishlistRepository,
        CustomPaginationInterface $customPagination,
        string                    $page,
    ): Response
    {
        $userWantlist = $wishlistRepository->getUserWishlist($user);

        $wishlistPaginate = $customPagination->pagination($userWantlist, $page, 6);

        $content = $twig->render('user/wantlist.html.twig', [
            'pagination' => $wishlistPaginate,
        ]);

        return new Response($content);
    }
}
