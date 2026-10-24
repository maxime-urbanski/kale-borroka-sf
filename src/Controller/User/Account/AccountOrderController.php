<?php

declare(strict_types=1);

namespace App\Controller\User\Account;

use App\Account\AccountCountsInterface;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Repository\ArticleRepository;
use App\Repository\OrderRepository;
use App\Service\CustomPaginationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

/**
 * Mes commandes (maquette 16): two crates, « En cours » always whole on top, « Terminées »
 * paginated by 9 below. A pending order waits for the customer's payment: it says how to pay.
 */
#[AsController]
class AccountOrderController
{
    private const int PER_PAGE = 9;

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route(
        path: '/mon-compte/mes-commandes/{page}',
        name: 'app_user_orders',
        requirements: [
            'page' => '^(page-)'.Requirement::DIGITS,
        ],
        defaults: [
            'page' => 'page-1',
        ],
        methods: [Request::METHOD_GET]
    )]
    public function __invoke(
        #[CurrentUser]
        User $user,
        OrderRepository $orderRepository,
        ArticleRepository $articleRepository,
        AccountCountsInterface $accountCounts,
        CustomPaginationInterface $customPagination,
        Environment $twig,
        string $page,
    ): Response {
        $ongoing = $orderRepository->ongoingOfBuyer($user);
        $finished = $customPagination->pagination($orderRepository->finishedOfBuyerQuery($user), $page, self::PER_PAGE);

        /** @var list<Order> $finishedOrders */
        $finishedOrders = [...$finished->getItems()];
        // Past the last page of the finished ones: nothing to show.
        if ([] === $finishedOrders && $finished->getCurrentPageNumber() > 1) {
            throw new NotFoundHttpException();
        }
        $orderRepository->loadLines($finishedOrders);
        // The list shows three covers per order.
        $articleRepository->preloadCovers(array_merge(...array_map(
            static fn (Order $order): array => \array_slice($order->getProducts(), 0, 3),
            [...$ongoing, ...$finishedOrders],
        )));

        return new Response($twig->render('user/order.html.twig', [
            'counts' => $accountCounts->of($user),
            'ongoing' => $ongoing,
            'awaitingPayment' => \count(array_filter($ongoing, static fn (Order $order): bool => OrderStatus::PENDING === $order->getStatus())),
            'finished' => $finished,
        ]));
    }
}
