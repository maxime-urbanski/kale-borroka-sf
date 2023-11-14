<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountUserCollectionController extends AbstractController
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    #[Route('/mon-compte/ma-collection', name: 'app_user_collection')]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function collection(): Response
    {
        $userCollection = $this->security->getUser()->getCollection();

        return $this->render('user/collection.html.twig', [
            'collection' => $userCollection,
        ]);
    }
}
