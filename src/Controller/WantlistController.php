<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Wantlist;
use App\Repository\ArticleRepository;
use App\Repository\WantlistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/wantlist', name: 'app_wantlist_')]
class WantlistController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ArticleRepository $articleRepository,
        private readonly WantlistRepository $wantlistRepository
    ) {
    }

    /**
     * @throws \Exception
     */
    #[Route('/add/{productId}', 'add')]
    public function addInWantlist(
        #[CurrentUser] User $user,
        string $productId
    ): Response {
        $articleToWantlist = $this->articleRepository->find($productId);

        if (!$user->getWantlist()) {
            $wantlist = new Wantlist();
            $wantlist->setUserWantlist($user);
            $this->entityManager->persist($wantlist);
        }

        $userWantlist = $user->getWantlist();
        $currentUserWantlist = $this->wantlistRepository->find($userWantlist);

        try {
            $currentUserWantlist->addProduct($articleToWantlist);
            $this->addFlash('success', $articleToWantlist->getName().' à bien été ajouté à la wantlist');
            $this->entityManager->persist($currentUserWantlist);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            throw new \Exception($exception);
        }

        return $this->redirectToRoute('app_homepage');
    }

    /**
     * @throws \Exception
     */
    #[Route('/remove/{productId}', 'remove')]
    public function removeInWantlist(
        #[CurrentUser] User $user,
        string $productId
    ): Response {
        $articleToWantlist = $this->articleRepository->find($productId);
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
