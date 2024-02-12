<?php

declare(strict_types=1);

namespace App\Controller\User\UserCollection;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
#[Route(
    path: '/mon-compte/ma-collection',
    name: 'app_user_collection',
    methods: [Request::METHOD_GET]
)]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class AccountUserCollectionController
{
    public function __invoke(
        #[CurrentUser]
        User $user,
        Environment $twig
    ): Response {
        $userCollection = $user->getCollection();
        $content = $twig->render('user/collection.html.twig', [
            'collection' => $userCollection,
        ]);

        return new Response($content);
    }
}
