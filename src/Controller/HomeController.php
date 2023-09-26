<?php

namespace App\Controller;

use App\Repository\SupportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    #[Route('/', 'app_homepage')]
    public function index(SupportRepository $supportRepository): Response
    {
        return $this->render('base.html.twig', [
            'support' => $supportRepository->findAll(),
        ]);
    }
}
