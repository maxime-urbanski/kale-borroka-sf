<?php

namespace App\Controller;

use App\Entity\Wantlist;
use App\Repository\ArticleRepository;
use App\Repository\WantlistRepository;
use Doctrine\ORM\EntityManagerInterface;
use mysql_xdevapi\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/wantlist', name: 'app_wantlist_')]
#[isGranted('IS_AUTHENTICATE_FULLY')]
class WantlistController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        private readonly ArticleRepository $articleRepository
    ) {
    }

    #[Route('/add/{productId}', 'add')]
    public function addInWantlist(
        string $productId,
        WantlistRepository $wantlistRepository
    ): Response {
        $articleToWantlist = $this->articleRepository->find($productId);
        $user = $this->security->getUser();

        if (!$user->getWantlist()) {
            $wantlist = new Wantlist();
            $user->setWantlist($wantlist);
            $this->entityManager->persist($user);
        }

        $userWantlist = $user->getWantlist();
        $currentUserWantlist = $wantlistRepository->find($userWantlist);

        try {
            $currentUserWantlist->addProduct($articleToWantlist);
            $this->addFlash('success', $articleToWantlist->getName().' à bien été ajouté à la wantlist');
            $this->entityManager->persist($currentUserWantlist);
            $this->entityManager->flush();
        } catch (Exception $exception) {
            throw new Exception($exception);
        }

        $this->entityManager->persist($currentUserWantlist);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_homepage');
    }

    #[Route('/remove/{productId}', 'remove')]
    public function removeInWantlist(
        string $productId,
        WantlistRepository $wantlistRepository
    ): Response {
        $articleToWantlist = $this->articleRepository->find($productId);
        $user = $this->security->getUser();
        $userWantlist = $user->getWantlist();
        $currentUserWantlist = $wantlistRepository->find($userWantlist);

        try {
            $currentUserWantlist->removeProduct($articleToWantlist);
            $this->addFlash('success', $articleToWantlist->getName().' à bien été supprimé de la wantlist');
            $this->entityManager->persist($currentUserWantlist);
            $this->entityManager->flush();
        } catch (Exception $exception) {
            throw new Exception($exception);
        }

        return $this->redirectToRoute('app_homepage');
    }
}
