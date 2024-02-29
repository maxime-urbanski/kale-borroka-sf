<?php

declare(strict_types=1);

namespace App\Controller\User\UserCollection;

use App\Entity\User;
use App\Repository\UserCollectionItemsRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
class AccountUserCollectionController
{
    #[Route(
        path: '/mon-compte/ma-collection',
        name: 'app_user_collection',
        methods: [Request::METHOD_GET]
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Environment $twig,
        UserCollectionItemsRepository $userCollectionItemsRepository
    ): Response {
        $userCollection = $user->getUserCollection() ?
            $userCollectionItemsRepository->findBy(['user_collection' => $user->getUserCollection()->getId()]) :
            [];

        $content = $twig->render('user/collection.html.twig', [
            'collection' => $userCollection,
            'collector' => $user->getUserCollection(),
        ]);

        return new Response($content);
    }
}
