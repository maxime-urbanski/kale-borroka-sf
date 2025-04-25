<?php

namespace App\Controller\Order;

use App\Data\OrderDeliveryDto;
use App\Entity\User;
use App\Form\OrderAddressDeliveryPaymentFormType;
use App\Mapper\Order\OrderDetailsMapper;
use App\Mapper\Order\OrderMapper;
use App\Repository\OrderDetailsRepository;
use App\Repository\OrderRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class OrderAddressDeliveryController extends AbstractController
{
    #[Route('/order/delivery', name: 'app_order_delivery')]
    public function selectAddressAndDelivery(
        #[CurrentUser] User $user,
        Request $request,
        CartService $cartService,
        OrderMapper $orderMapper,
        OrderRepository $orderRepository,
        OrderDetailsMapper $orderDetailsMapper,
        OrderDetailsRepository $orderDetailsRepository,
    ): Response {
        $cart = $cartService->getFullCart();

        $orderDeliveryDto = new OrderDeliveryDto();
        $form = $this->createForm(OrderAddressDeliveryPaymentFormType::class, $orderDeliveryDto, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $totalPrice = 0;

            $order = $orderMapper->mapDataToOrderResource($user, $orderDeliveryDto);

            foreach ($cart as $product) {
                $orderDetails = $orderDetailsMapper->mapDataToOrderDetailsResource($product, $order);
                $totalPrice += $orderDetails->getPrice();
                $orderDetailsRepository->save($orderDetails, true);
            }

            $order->setTotalPrice($totalPrice);
            $orderRepository->save($order, true);

            $cartService->removeAll();

            return $this->forward('App\\Controller\\Order\\OrderOverview::overview', [
                'orderReference' => $order->getReference(),
            ]);
        }

        return $this->render('order/delivery.html.twig', [
            'cart' => $cartService->getFullCart(),
            'form' => $form->createView(),
        ]);
    }
}
