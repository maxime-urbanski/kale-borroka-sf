<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductionController extends AbstractController
{
    #[Route('/production', 'app_production')]
    public function index(ArticleRepository $articleRepository): Response
    {
        $productions = $articleRepository->getOwnProduction();

        return $this->render('production/index.html.twig', [
            'productions' => $productions->getResult()
        ]);
    }
}
