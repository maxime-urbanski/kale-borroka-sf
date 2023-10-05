<?php

namespace App\Controller\Order;

use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Form\OrderAddressDeliveryPaymentFormType;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderAddressDeliveryController extends AbstractController
{

    #[Route('/order/delivery', name: 'app_order_delivery')]
    public function selectAddressAndDelivery(
        Request                $request,
        Security               $security,
        CartService            $cartService,
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = $security->getUser();
        $cart = $cartService->getFullCart();

        $form = $this->createForm(OrderAddressDeliveryPaymentFormType::class, null, [
            'user' => $user
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order = new Order();
            $reference = 'kbr-' . uniqid();
            $totalPrice = 0;

            $addressSelected = $form->getData()['address'];
            $transporterSelected = $form->getData()['transport'];
            $paymentSelected = $form->getData()['payment'];

            $order->setReference($reference);
            $order->setBuyer($user);
            $order->setCreatedAt(new \DateTimeImmutable('now'));
            $order->setStatus('PROCESS');
            $order->setAddress($addressSelected);
            $order->setDelivery($transporterSelected);
            $order->setPayment($paymentSelected);


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
            }

            $entityManager->persist($order);
            $entityManager->flush();

            $cartService->removeAll();


            return $this->redirectToRoute('app_order_overview',[
                'orderReference' => $order->getReference()
            ]);
        }

        return $this->render('order/delivery.html.twig', [
            'cart' => $cartService->getFullCart(),
            'form' => $form->createView()
        ]);
    }
}
