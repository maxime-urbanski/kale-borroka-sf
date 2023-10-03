<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/order', name: 'app_order_')]
#[isGranted('IS_AUTHENTICATED_FULLY')]
class OrderController extends AbstractController
{
    public function __construct(private readonly CartService $cartService)
    {
    }

    #[Route('/create', name: 'create')]
    public function create(): Response
    {
        $cart = $this->cartService->getFullCart();

        return $this->render('');
    }
}
