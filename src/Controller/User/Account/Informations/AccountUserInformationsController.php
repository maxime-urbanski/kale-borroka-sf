<?php

declare(strict_types=1);

namespace App\Controller\User\Account\Informations;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
class AccountUserInformationsController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/mon-compte',
        name: 'app_user_informations'
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Environment $twig
    ): Response {
        $content = $twig->render('user/_informations.html.twig', [
            'user' => $user,
        ]);

        return new Response($content);
    }
}
