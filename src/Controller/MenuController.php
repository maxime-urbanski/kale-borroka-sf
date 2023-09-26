<?php

namespace App\Controller;

use App\Repository\SupportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends AbstractController
{
    public function __construct(private readonly SupportRepository $supportRepository)
    {
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

        return $this->render('layout/_navbar.html.twig', [
            'links' => $links,
        ]);
    }
}
