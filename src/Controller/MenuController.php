<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SupportRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends AbstractController
{
    public function __construct(
        private readonly SupportRepository $supportRepository,
        private readonly CartService $cartService,
    ) {
    }

    public function navbar(): Response
    {
        $links = [
            'production' => [
                'link' => 'app_production',
                'name' => 'Nos productions',
            ],
            'catalog' => [
                'link' => 'app_catalog',
                'name' => 'Catalogue',
                'child' => $this->supportRepository->findAll(),
            ],
        ];

        $cart = $this->cartService->getFullCart();
        $cartQuantity = 0;

        foreach ($cart as $product) {
            $cartQuantity = $cartQuantity + $product['quantity'];
        }

        return $this->render('layout/_navbar.html.twig', [
            'links' => $links,
            'itemsInCart' => $cartQuantity > 9 ? '9+' : $cartQuantity,
        ]);
    }
}
