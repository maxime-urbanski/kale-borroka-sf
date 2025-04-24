<?php

namespace App\Controller\Order;

use App\Data\OrderDeliveryDto;
use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Entity\User;
use App\Form\OrderAddressDeliveryPaymentFormType;
use App\Repository\UserCollectionRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
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
        EntityManagerInterface $entityManager,
        UserCollectionRepository $userCollectionRepository,
    ): Response {
        $cart = $cartService->getFullCart();

        $OrderDeliveryDto = new OrderDeliveryDto();
        $form = $this->createForm(OrderAddressDeliveryPaymentFormType::class, $OrderDeliveryDto, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order = new Order();

            $reference = 'kbr-'.uniqid('', true);
            $totalPrice = 0;

            $order->setReference($reference);
            $order->setBuyer($user);
            $order->setCreatedAt(new \DateTimeImmutable('now'));
            $order->setStatus('PROCESS');
            $order->setAddress($OrderDeliveryDto->deliveryAddress);
            $order->setDelivery($OrderDeliveryDto->transporter);
            $order->setPayment($OrderDeliveryDto->paymentMethod);

            foreach ($cart as $product) {
                $orderDetails = new OrderDetails();
                $orderDetails->setProduct($product['product']);

                if ($product['quantity'] > $product['quantityMaxAvailable']) {
                    $orderDetails->setQuantity($product['quantityMaxAvailable']);
                } else {
                    $orderDetails->setQuantity($product['quantity']);
                }

                $orderDetails->setPrice($product['product']->getPrice() * $orderDetails->getQuantity());
                $totalPrice += $orderDetails->getPrice();

                $orderDetails->setOrders($order);
                $order->setTotalPrice($totalPrice);

                $entityManager->persist($orderDetails);
                // $entityManager->persist($getUserCollection);
            }

            $entityManager->persist($order);
            $entityManager->flush();

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
