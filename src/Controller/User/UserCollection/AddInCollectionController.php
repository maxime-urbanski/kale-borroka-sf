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
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class AddInCollectionController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/collection/add/{productId}',
        name: 'app_collection_add',
        requirements: ['productId' => Requirement::DIGITS],
        methods: [Request::METHOD_GET]
    )]
    public function __invoke(
        #[CurrentUser]
        User                     $user,
        #[MapEntity(mapping: ['productId' => 'id'])]
        Article                  $article,
        RefererInterface         $referer,
        EntityManagerInterface   $entityManager,
        UserCollectionRepository $userCollectionRepository,
        Request                  $request
    ): RedirectResponse
    {
        if (!$user->getUserCollection()) {
            $collection = new UserCollection();
            $collection->setUserCollection($user);
            $entityManager->persist($collection);
        } else {
            $collection = $userCollectionRepository->findOneBy(['user_collection' => $user]);
        }

        /** @var Session $session */
        $session = $request->getSession();

        try {
            $collection->addArticle($article);
            $collection->setSince(new \DateTime('now'));
            $session->getFlashbag()->add('success', $article->getName() . ' a bien été ajouté à ta collection');
            $entityManager->persist($collection);
            $entityManager->flush();
        } catch (NotFoundHttpException $exception) {
            $session->getFlashbag()->add('danger', $exception);
        }

        return new RedirectResponse($referer->getReferer());
    }
}
