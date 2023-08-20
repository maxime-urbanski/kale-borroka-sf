<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductionController extends AbstractController
{
    #[Route('/production/{page}', 'app_production', requirements: ['page' => '^(page-)\d+'])]
    public function index(
        ArticleRepository $articleRepository,
        PaginationService $paginationService,
        string            $page = 'page-1'
    ): Response
    {
        $productions = $articleRepository->getOwnProduction();
        $pagination = $paginationService->pagination($productions, $page);

        return $this->render('production/index.html.twig', [
            'productions' => $pagination,
        ]);
    }
}
