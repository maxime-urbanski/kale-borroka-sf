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
        $supports = $this->supportRepository->findAll();
        $production = [
            'link' => 'app_production',
            'name' => 'Production'
        ];

        return $this->render('layout/_navbar.html.twig', [
            'supports' =>$supports,
            'production' => $production
        ]);
    }
}
