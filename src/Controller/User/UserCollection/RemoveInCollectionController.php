<?php

declare(strict_types=1);

namespace App\Controller\User\UserCollection;

use App\Entity\Article;
use App\Entity\User;
use App\Repository\UserCollectionItemsRepository;
use App\Repository\UserCollectionRepository;
use App\Service\RefererInterface;
use Doctrine\ORM\EntityManagerInterface;
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
class RemoveInCollectionController
{
    /**
     * @throws NonUniqueResultException
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/collection/remove/{productId}',
        name: 'app_collection_remove',
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
        UserCollectionItemsRepository $userCollectionItemsRepository,
        Request $request,
    ): RedirectResponse {
        $currentUserCollection = $userCollectionRepository->getUserCollection($user)->getOneOrNullResult();

        $currentItemToRemove = $userCollectionItemsRepository->getUserCollectionItem($article, $currentUserCollection);

        /** @var Session $session */
        $session = $request->getSession();

        try {
            $userCollectionItemsRepository->remove($currentItemToRemove, true);
            $session
                ->getFlashbag()
                ->add(
                    'success',
                    $article->getName().' à bien été supprimé de ta collection'
                );
        } catch (NotFoundHttpException $exception) {
            $session->getFlashbag()->add('danger', $exception);
        }

        return new RedirectResponse($referer->getReferer());
    }
}
