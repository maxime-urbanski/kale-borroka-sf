<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountUserCollectionController extends AbstractController
{

    #[Route('/mon-compte/ma-collection', name: 'app_user_collection')]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function collection(#[CurrentUser] User $user): Response
    {
        $userCollection = $user->getCollection();

        return $this->render('user/collection.html.twig', [
            'collection' => $userCollection,
        ]);
    }
}
