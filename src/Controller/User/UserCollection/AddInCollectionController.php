<?php

declare(strict_types=1);

namespace App\Controller\User\UserCollection;

use App\Entity\Article;
use App\Entity\User;
use App\Entity\UserCollectionItems;
use App\Repository\UserCollectionItemsRepository;
use App\Repository\UserCollectionRepository;
use App\Service\RefererInterface;
use Doctrine\ORM\NonUniqueResultException;
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
    /**
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
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
        UserCollectionRepository $userCollectionRepository,
        UserCollectionItemsRepository $userCollectionItemsRepository,
        Request $request,
    ): RedirectResponse {
        $userCollection = $userCollectionRepository->getUserCollection($user)->getOneOrNullResult();
        /** @var Session $session */
        $session = $request->getSession();

        try {
            $userCollectionItems = new UserCollectionItems();
            $userCollectionItems->setCollection($userCollection);
            $userCollectionItems->setArticle($article);
            $userCollectionItems->setAddedAt(
                new \DateTimeImmutable('now',
                    new \DateTimeZone('Europe/Paris')
                )
            );

            $userCollectionItemsRepository->save($userCollectionItems, true);
            $session->getFlashbag()->add('success', $article->getName().' a bien été ajouté à ta collection');
        } catch (NotFoundHttpException $exception) {
            $session->getFlashbag()->add('danger', $exception);
        }

        return new RedirectResponse($referer->getReferer());
    }
}
