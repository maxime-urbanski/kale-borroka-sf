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

class OrderPaymentController extends AbstractController
{
    /**
     * Placeholder until PayPal / Stripe: the order is already guarded like its page, so
     * the payment flow inherits the owner check.
     */
    #[Route('/order/{orderReference}/payment/choice', name: 'app_order_payment_choice')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted(OrderVoter::OWN, subject: 'order')]
    public function paymentPreparation(
        #[MapEntity(mapping: ['orderReference' => 'reference'])]
        Order $order,
    ): Response {
        // TODO: ADD PAYPAL AND STRIPE
        $this->addFlash('info', 'Le paiement en ligne arrive bientôt.');

        return $this->redirectToRoute('app_user_order', ['orderReference' => $order->getReference()]);
    }
}
