<?php

namespace App\Controller;

use App\Data\ProductionFilterData;
use App\Form\ProductionFilterFormType;
use App\Repository\ArticleRepository;
use App\Repository\SupportRepository;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductionController extends AbstractController
{
    #[Route('/production/{page}', 'app_production', requirements: ['page' => '^(page-)\d+'])]
    public function index(
        ArticleRepository $articleRepository,
        PaginationService $paginationService,
        SupportRepository $support,
        Request $request,
        string $page = 'page-1'
    ): Response {
        $breadcrumb = [
            [
                'name' => 'Accueil',
                'path' => 'app_homepage',
            ],
            [
                'name' => 'Nos Productions',
                'path' => 'app_production',
            ],
        ];

        $data = new ProductionFilterData();
        $data->kbrProduction = true;

        $productionForm = $this->createForm(ProductionFilterFormType::class, $data);
        $productionForm->handleRequest($request);

        $productions = $articleRepository->filterArticleQuery($data);
        $pagination = $paginationService->pagination($productions, $page);

        return $this->render('catalog/articles.html.twig', [
            'articles' => $pagination,
            'breadcrumb' => $breadcrumb,
            'form' => $productionForm->createView(),
        ]);
    }
}
