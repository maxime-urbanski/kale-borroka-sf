<?php

declare(strict_types=1);

namespace App\Controller\User\UserCollection;

use App\Entity\User;
use App\Repository\UserCollectionRepository;
use App\Service\CustomPaginationInterface;
use Doctrine\ORM\NonUniqueResultException;
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
class AccountUserCollectionController
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws NonUniqueResultException
     * @throws LoaderError
     */
    #[Route(
        path: '/mon-compte/collection/{page}',
        name: 'app_user_collection',
        requirements: [
            'page' => '^(page-)'.Requirement::DIGITS,
        ],
        defaults: [
            'page' => 'page-1',
        ],
        methods: [Request::METHOD_GET]
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Environment $twig,
        UserCollectionRepository $userCollectionRepository,
        CustomPaginationInterface $customPagination,
        string $page,
    ): Response {
        $userCollection = $userCollectionRepository->getUserCollection($user);

        $userCollectionPaginate = $customPagination->pagination($userCollection, $page);

        $content = $twig->render('user/collection.html.twig', [
            'pagination' => $userCollectionPaginate,
        ]);

        return new Response($content);
    }
}
