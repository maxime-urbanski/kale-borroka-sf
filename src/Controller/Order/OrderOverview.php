<?php

declare(strict_types=1);

namespace App\Controller\Order;

use App\Entity\Order;
use App\Security\Voter\OrderVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OrderOverview extends AbstractController
{
    #[Route('/order/{orderReference}/overview', name: 'app_order_overview')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted(OrderVoter::VIEW, subject: 'order')]
    public function overview(
        #[MapEntity(mapping: ['orderReference' => 'reference'])]
        Order $order,
    ): Response {
        return $this->render('order/overview.html.twig', [
            'order' => $order,
        ]);
    }
}
