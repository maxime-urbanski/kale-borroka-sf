<?php

declare(strict_types=1);

namespace App\Controller\User\Account;

use App\Account\AccountCountsInterface;
use App\Account\OrderArticlesInterface;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Repository\ArticleRepository;
use App\Security\Voter\OrderVoter;
use App\Service\ShopSettingsProviderInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

/**
 * Détail d'une commande (maquette 17): where it stands, one block saying what comes next for
 * its status, the lines, the recap, the delivery and the payment. Right after the checkout it
 * opens with the thank-you banner instead of that block (maquette 18), once.
 */
#[AsController]
class AccountOrderDetailsController
{
    /** Session key: the reference of the order the checkout just placed. */
    public const string JUST_PLACED = 'order.just_placed';

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted(OrderVoter::OWN, subject: 'order')]
    #[Route(
        path: '/mon-compte/mes-commandes/{orderReference}',
        name: 'app_user_order',
        requirements: [
            'orderReference' => 'KBR-[\w-]+',
        ],
        methods: [Request::METHOD_GET]
    )]
    public function __invoke(
        #[MapEntity(mapping: ['orderReference' => 'reference'])]
        Order $order,
        #[CurrentUser]
        User $user,
        Request $request,
        Environment $twig,
        AccountCountsInterface $accountCounts,
        ArticleRepository $articleRepository,
        OrderArticlesInterface $orderArticles,
        ShopSettingsProviderInterface $shopSettings,
    ): Response {
        $session = $request->getSession();
        $thanks = $session->get(self::JUST_PLACED) === $order->getReference();
        if ($thanks) {
            $session->remove(self::JUST_PLACED);
        }

        $articleRepository->preloadCovers($order->getProducts());
        $finished = \in_array($order->getStatus(), OrderStatus::finished(), true);

        return new Response($twig->render('user/order_details.html.twig', [
            'order' => $order,
            'user' => $user,
            'counts' => $accountCounts->of($user),
            'thanks' => $thanks,
            'inStock' => $finished ? $orderArticles->inStock($order) : [],
            'toCollect' => OrderStatus::DELIVERED === $order->getStatus() ? $orderArticles->notCollectedBy($order, $user) : [],
            'contactEmail' => $shopSettings->get()->getContactEmail(),
        ]));
    }
}
