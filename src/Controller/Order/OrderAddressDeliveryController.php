<?php

declare(strict_types=1);

namespace App\Controller\Order;

use App\Data\OrderDeliveryDto;
use App\Entity\User;
use App\Form\OrderAddressDeliveryPaymentFormType;
use App\Messenger\CommandBusInterface;
use App\Order\Command\PlaceOrder;
use App\Order\Exception\InvalidOrderException;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
        CommandBusInterface $commandBus,
    ): Response {
        $orderDeliveryDto = new OrderDeliveryDto();
        $form = $this->createForm(OrderAddressDeliveryPaymentFormType::class, $orderDeliveryDto, []);
        $form->handleRequest($request);
        $cart = $cartService->getFullCart();

        if ($form->isSubmitted() && $form->isValid()) {
            $lines = [];

            foreach ($cart as $item) {
                $lines[(int) $item['product']->getId()] = $item['quantity'];
            }

            try {
                $reference = $commandBus->dispatch(new PlaceOrder(
                    buyerId: (int) $user->getId(),
                    addressId: (int) $orderDeliveryDto->deliveryAddress?->getId(),
                    transporterId: (int) $orderDeliveryDto->transporter?->getId(),
                    paymentId: (int) $orderDeliveryDto->paymentMethod?->getId(),
                    lines: $lines,
                ));
            } catch (InvalidOrderException $exception) {
                $this->addFlash('danger', $exception->getMessage());

                return $this->redirectToRoute('app_cart_index');
            }

            $cartService->removeAll();

            return $this->redirectToRoute('app_order_overview', ['orderReference' => $reference]);
        }

        return $this->render('order/delivery.html.twig', [
            'cart' => $cart,
            'form' => $form->createView(),
        ]);
    }
}
