<?php

declare(strict_types=1);

namespace App\Controller\User\Account;

use App\Account\OrderArticlesInterface;
use App\Entity\Order;
use App\Entity\User;
use App\Entity\UserCollection;
use App\Entity\UserCollectionItems;
use App\Enum\OrderStatus;
use App\Repository\UserCollectionRepository;
use App\Security\ActionCsrfToken;
use App\Security\Voter\OrderVoter;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * « Ajouter à ma collection » on a delivered order (maquette 17): its records the collection
 * does not hold yet. Before delivery the records are not in the customer's hands: refused.
 */
#[AsController]
class CollectOrderController
{
    /**
     * @throws NonUniqueResultException
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted(OrderVoter::OWN, subject: 'order')]
    #[Route(
        path: '/mon-compte/mes-commandes/{orderReference}/collection',
        name: 'app_user_order_collect',
        requirements: [
            'orderReference' => 'KBR-[\w-]+',
        ],
        methods: [Request::METHOD_POST]
    )]
    public function __invoke(
        #[MapEntity(mapping: ['orderReference' => 'reference'])]
        Order $order,
        #[CurrentUser]
        User $user,
        Request $request,
        ActionCsrfToken $actionCsrfToken,
        OrderArticlesInterface $orderArticles,
        UserCollectionRepository $userCollectionRepository,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
    ): RedirectResponse {
        $redirect = new RedirectResponse($urlGenerator->generate('app_user_order', ['orderReference' => $order->getReference()]), Response::HTTP_SEE_OTHER);

        if (!$actionCsrfToken->isValid($request, ActionCsrfToken::COLLECTION)) {
            return $redirect;
        }

        /** @var Session $session */
        $session = $request->getSession();

        if (OrderStatus::DELIVERED !== $order->getStatus()) {
            $session->getFlashBag()->add('info', 'Ta collection attend que la commande soit livrée.');

            return $redirect;
        }

        // Accounts created before the lists existed have no collection yet.
        $collection = $userCollectionRepository->getUserCollection($user)->getOneOrNullResult();
        if (null === $collection) {
            $collection = (new UserCollection())->setUser($user);
            $entityManager->persist($collection);
        }

        $articles = $orderArticles->notCollectedBy($order, $user);
        $addedAt = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        foreach ($articles as $article) {
            $entityManager->persist((new UserCollectionItems())->setCollection($collection)->setArticle($article)->setAddedAt($addedAt));
        }

        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            // Double click: the other request added them between the check and this insert.
            $session->getFlashBag()->add('info', 'Ces disques sont déjà dans ta collection.');

            return $redirect;
        }

        $session->getFlashBag()->add(
            [] === $articles ? 'info' : 'success',
            match (\count($articles)) {
                0 => 'Ces disques sont déjà dans ta collection.',
                1 => '1 disque ajouté à ta collection.',
                default => \count($articles).' disques ajoutés à ta collection.',
            },
        );

        return $redirect;
    }
}
