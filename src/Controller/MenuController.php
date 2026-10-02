<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SupportRepository;
use App\Service\CartInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends AbstractController
{
    public function __construct(
        private readonly SupportRepository $supportRepository,
        private readonly CartInterface $cartService,
        private readonly RequestStack $requestStack,
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
            'active' => $this->activeLink(),
            'itemsInCart' => $cartQuantity > 9 ? '9+' : $cartQuantity,
        ]);
    }

    /**
     * The section of the page being displayed: this is a sub-request, the route is the main request's.
     */
    private function activeLink(): ?string
    {
        $route = (string) $this->requestStack->getMainRequest()?->attributes->get('_route');

        return match (true) {
            str_starts_with($route, 'app_production') => 'production',
            str_starts_with($route, 'app_catalog') => 'catalog',
            default => null,
        };
    }
}
