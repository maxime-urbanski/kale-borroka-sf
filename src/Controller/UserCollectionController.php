<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Article;
use App\Entity\User;
use App\Entity\UserCollection;
use App\Repository\UserCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/collection', name: 'app_collection_')]
class UserCollectionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserCollectionRepository $userCollectionRepository,
    ) {
    }

    #[Route('/add/{productId}', name: 'add')]
    public function add(
        #[MapEntity(mapping: ['productId' => 'id'])] Article $article,
        #[CurrentUser] User $user
    ): Response {
        if (!$user->getCollection()) {
            $collection = new UserCollection();
            $user->setCollection($collection);
            $this->entityManager->persist($user);
        }

        $userCollection = $user->getCollection();
        $currentUserCollection = $this->userCollectionRepository->find($userCollection);

        try {
            $currentUserCollection->addArticle($article);
            $currentUserCollection->setSince(new \DateTime('now'));
            $this->addFlash('success', $article->getName().' à bien été ajouté à ta collection');
            $this->entityManager->persist($currentUserCollection);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            throw $this->createNotFoundException('Une erreur survenue lors de la mise en collection');
        }

        return $this->redirectToRoute('app_homepage');
    }

    #[Route('/remove/{productId}', name: 'remove')]
    public function removeToCollection(
        #[MapEntity(mapping: ['productId' => 'id'])] Article $article,
        #[CurrentUser] User $user
    ): Response {
        $userCollection = $user->getCollection();
        $currentUserCollection = $this->userCollectionRepository->find($userCollection);

        try {
            $currentUserCollection->removeArticle($article);
            $this->addFlash('success', $article->getName().' à bien été supprimé de ta collection');
            $this->entityManager->persist($currentUserCollection);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            throw $this->createNotFoundException('Une erreur est survenue');
        }

        return $this->redirectToRoute('app_homepage');
    }
}
