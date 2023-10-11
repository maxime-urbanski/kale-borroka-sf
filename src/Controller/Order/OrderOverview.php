<?php

namespace App\Controller\Order;


use App\Repository\OrderRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderOverview extends AbstractController
{
    #[Route('/order/{orderReference}/overview', name: 'app_order_overview')]
    public function overview(OrderRepository $orderRepository, string $orderReference, CartService $cartService): Response
    {
        $order = $orderRepository->findOneBy(['reference' => $orderReference ]);

        return $this->render('order/overview.html.twig', [
            'order' => $order
        ]);
    }
}
