<?php

declare(strict_types=1);

namespace App\Controller\User\UserCollection;

use App\Entity\Article;
use App\Entity\User;
use App\Entity\UserCollectionItems;
use App\Repository\UserCollectionItemsRepository;
use App\Repository\UserCollectionRepository;
use App\Security\ActionCsrfToken;
use App\Service\RefererInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
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
        methods: [Request::METHOD_POST]
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
        ActionCsrfToken $actionCsrfToken,
    ): RedirectResponse {
        if (!$actionCsrfToken->isValid($request, ActionCsrfToken::COLLECTION)) {
            return new RedirectResponse($referer->getReferer());
        }

        // Drafts are invisible in the shop: do not let them in through a guessed id either.
        if (!$article->isPublished()) {
            throw new NotFoundHttpException();
        }

        $userCollection = $userCollectionRepository->getUserCollection($user)->getOneOrNullResult();
        /** @var Session $session */
        $session = $request->getSession();

        // Already added from another tab: the composite key would refuse a second row.
        if (null !== $userCollection && null !== $userCollectionItemsRepository->getUserCollectionItem($article, $userCollection)) {
            $session->getFlashbag()->add('danger', 'Cet article est déjà dans ta collection.');

            return new RedirectResponse($referer->getReferer());
        }

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

        return new RedirectResponse($referer->getReferer());
    }
}
