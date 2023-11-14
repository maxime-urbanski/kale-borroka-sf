<?php

namespace App\Controller;

use App\Entity\Wantlist;
use App\Repository\ArticleRepository;
use App\Repository\WantlistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/wantlist', name: 'app_wantlist_')]
class WantlistController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
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
        string $productId
    ): Response {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $articleToWantlist = $this->articleRepository->find($productId);

        if (!$user->getWantlist()) {
            $wantlist = new Wantlist();
            $user->setWantlist($wantlist);
            $this->entityManager->persist($user);
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

    #[Route('/remove/{productId}', 'remove')]
    public function removeInWantlist(
        string $productId
    ): Response {
        $articleToWantlist = $this->articleRepository->find($productId);
        $user = $this->security->getUser();
        $userWantlist = $user->getWantlist();
        $currentUserWantlist = $this->wantlistRepository->find($userWantlist);

        try {
            $currentUserWantlist->removeProduct($articleToWantlist);
            $this->addFlash('success', $articleToWantlist->getName().' à bien été supprimé de la wantlist');
            $this->entityManager->persist($currentUserWantlist);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            throw new \Exception($exception);
        }

        return $this->redirectToRoute('app_homepage');
    }
}
