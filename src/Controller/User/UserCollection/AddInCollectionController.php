<?php

declare(strict_types=1);

namespace App\Controller\User\UserCollection;

use App\Entity\Article;
use App\Entity\User;
use App\Entity\UserCollection;
use App\Repository\UserCollectionRepository;
use App\Service\RefererInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[asController]
#[isGranted('IS_AUTHENTICATED_FULLY')]
class AddInCollectionController
{
    #[Route(
        path: '/collection/add/{productId}',
        name: 'app_collection_add',
        requirements: ['productId' => Requirement::DIGITS],
        methods: [Request::METHOD_GET]
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        #[MapEntity(mapping: ['productId' => 'id'])]
        Article $article,
        RefererInterface $referer,
        EntityManagerInterface $entityManager,
        UserCollectionRepository $userCollectionRepository,
        SessionInterface $session
    ): RedirectResponse {
        if (!$user->getCollection()) {
            $collection = new UserCollection();
            $collection->setCollector($user);
            $entityManager->persist($collection);
        }

        $currentUserCollection = $userCollectionRepository->findOneBy(
            ['collector' => $user]
        );

        try {
            $currentUserCollection->addArticle($article);
            $currentUserCollection->setSince(new \DateTime('now'));
            $session->getFlashbag()->add('success', $article->getName().' a bien été ajouté à ta collection');
            $entityManager->persist($currentUserCollection);
            $entityManager->flush();
        } catch (NotFoundHttpException $exception) {
            $session->getFlashbag()->add('danger', $exception);
        }

        return new RedirectResponse($referer->getReferer());
    }
}
