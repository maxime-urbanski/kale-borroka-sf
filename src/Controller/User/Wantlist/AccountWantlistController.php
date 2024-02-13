<?php

declare(strict_types=1);

namespace App\Controller\User\Wantlist;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
class AccountWantlistController extends AbstractController
{
    #[Route(
        path: '/mon-compte/ma-wantlist',
        name: 'app_user_wantlist',
        methods: [Request::METHOD_GET]
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function __invoke(
        #[CurrentUser]
        User $user,
        Environment $twig
    ): Response {
        $userWantlist = $user->getWantlist();
        $content = $twig->render('user/wantlist.html.twig', [
            'wantlist' => $userWantlist,
        ]);

        return new Response($content);
    }
}
