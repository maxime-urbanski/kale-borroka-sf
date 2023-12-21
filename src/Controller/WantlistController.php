<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Article;
use App\Entity\User;
use App\Entity\Wantlist;
use App\Repository\WantlistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/wantlist', name: 'app_wantlist_')]
class WantlistController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WantlistRepository $wantlistRepository
    ) {
    }

    /**
     * @throws \Exception
     */
    #[Route('/add/{productId}', 'add')]
    public function addInWantlist(
        #[CurrentUser] User $user,
        #[MapEntity(mapping: ['productId' => 'id'])] Article $articleToWantlist
    ): Response {
        if (!$user->getWantlist()) {
            $wantlist = new Wantlist();
            $wantlist->setUserWantlist($user);
            $this->entityManager->persist($wantlist);
            $this->entityManager->flush();
        }

        $userWantlist = $user->getWantlist();
        $currentUserWantlist = $this->wantlistRepository->find($userWantlist);

        try {
            $currentUserWantlist->addProduct($articleToWantlist);
            $this->addFlash('success', $articleToWantlist->getName().' à bien été ajouté à la wantlist');
            $this->entityManager->persist($currentUserWantlist);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            throw $this->createNotFoundException('Une erreur est survenue');
        }

        return $this->redirectToRoute('app_homepage');
    }

    /**
     * @throws \Exception
     */
    #[Route('/remove/{productId}', 'remove')]
    public function removeInWantlist(
        #[CurrentUser] User $user,
        #[MapEntity(mapping: ['productId' => 'id'])] Article $articleToWantlist
    ): Response {
        $userWantlist = $user->getWantlist();
        $currentUserWantlist = $this->wantlistRepository->find($userWantlist);

        try {
            $currentUserWantlist->removeProduct($articleToWantlist);
            $this->addFlash('success', $articleToWantlist->getName().' à bien été supprimé de la wantlist');
            $this->entityManager->persist($currentUserWantlist);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            throw new \Exception('error');
        }

        return $this->redirectToRoute('app_homepage');
    }
}
