<?php

namespace App\Controller\User;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mon-compte', name: 'app_user_')]
#[isGranted('IS_AUTHENTICATED_FULLY')]
class AccountInformationController extends AbstractController
{
    #[Route('', 'informations')]
    public function userInformation(): Response
    {
        $user = $this->getUser();

        return $this->render('user/_informations.html.twig', [
            'user' => $user
        ]);
    }
}
