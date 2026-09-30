<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SupportRepository;
use App\Service\CartInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends AbstractController
{
    public function __construct(
        private readonly SupportRepository $supportRepository,
        private readonly CartInterface $cartService,
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

        // This sub-request runs on every page: one scalar query at most (CartService::countItems()).
        $cartQuantity = $this->cartService->countItems();

        return $this->render('layout/_navbar.html.twig', [
            'links' => $links,
            'itemsInCart' => $cartQuantity > 9 ? '9+' : $cartQuantity,
        ]);
    }
}
