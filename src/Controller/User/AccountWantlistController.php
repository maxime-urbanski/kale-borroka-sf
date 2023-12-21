<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountWantlistController extends AbstractController
{
    #[Route('/mon-compte/ma-wantlist', name: 'app_user_wantlist')]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function wantlist(
        #[CurrentUser] User $user
    ): Response {
        $userWantlist = $user->getWantlist();

        return $this->render('user/wantlist.html.twig', [
            'wantlist' => $userWantlist,
        ]);
    }
}
