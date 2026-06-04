<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\SupportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    #[Route('/', 'app_homepage')]
    public function index(
        SupportRepository $supportRepository,
        ArticleRepository $articleRepository,
    ): Response {
        $lastArticle = $articleRepository->getLastArticle();
        $lasProduction = $articleRepository->getOwnProduction(true);

        return $this->render('home/index.html.twig', [
            'support' => $supportRepository->findAll(),
            'lastArticle' => $lastArticle->getResult(),
            'lastProduction' => $lasProduction->getResult(),
        ]);
    }
}
