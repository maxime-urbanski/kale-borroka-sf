<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountWantlistController extends AbstractController
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    #[Route('/mon-compte/ma-wantlist', name: 'app_user_wantlist')]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function wantlist(): Response
    {
        $userWantlist = $this->security->getUser()->getWantlist()->getProduct();

        return $this->render('user/wantlist.html.twig', [
            'wantlist' => $userWantlist,
        ]);
    }
}
