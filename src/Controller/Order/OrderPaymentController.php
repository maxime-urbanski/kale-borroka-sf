<?php

namespace App\Controller\Order;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderPaymentController extends AbstractController
{
    #[Route('/order/{orderReference}/payment/choice', name: 'app_order_payment_choice')]
    public function paymentPreparation(): Response

    {

        return $this->render('');

    }
}
