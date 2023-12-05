<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserCollection;
use App\Repository\ArticleRepository;
use App\Repository\UserCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/collection', name: 'app_collection_')]
class UserCollectionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface   $entityManager,
        private readonly UserCollectionRepository $userCollectionRepository,
        private readonly ArticleRepository        $articleRepository
    )
    {
    }

    /**
     * @throws \Exception
     */
    #[Route('/add/{productId}', name: 'add')]
    public function add(
        #[CurrentUser] User $user,
        string $productId
    ): Response
    {
        $article = $this->articleRepository->find($productId);

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
            $this->addFlash('success', $article->getName() . ' à bien été ajouté à ta collection');
            $this->entityManager->persist($currentUserCollection);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            throw new \Exception('error');
        }

        return $this->redirectToRoute('app_homepage');
    }

    /**
     * @throws \Exception
     */
    #[Route('/remove/{productId}', name: 'remove')]
    public function removeToCollection(
        #[CurrentUser] User $user,
        string $productId
    ): Response
    {
        $article = $this->articleRepository->find($productId);

        $userCollection = $user->getCollection();
        $currentUserCollection = $this->userCollectionRepository->find($userCollection);

        try {
            $currentUserCollection->removeArticle($article);
            $this->addFlash('success', $article->getName() . ' à bien été supprimé de ta collection');
            $this->entityManager->persist($currentUserCollection);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            throw new \Exception('error');
        }

        return $this->redirectToRoute('app_homepage');
    }
}
