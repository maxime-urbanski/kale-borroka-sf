<?php

declare(strict_types=1);

namespace App\Controller;

use App\Data\ArticleFilterData;
use App\Form\ProductionFilterFormType;
use App\Repository\ArticleRepository;
use App\Service\DispatchFilterValueService;
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
        DispatchFilterValueService $dispatchFilterValueService,
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

        $data = new ArticleFilterData();
        $data->globalFilters['kbrProduction'] = true;

        $productionForm = $this->createForm(
            ProductionFilterFormType::class,
            $data
        );
        $productionForm->handleRequest($request);

        $productions = $articleRepository
            ->filterArticleQuery($dispatchFilterValueService->dispatchFilterValue($data));
        $pagination = $paginationService->pagination($productions, $page);

        return $this->render('catalog/articles.html.twig', [
            'articles' => $pagination,
            'breadcrumb' => $breadcrumb,
            'form' => $productionForm->createView(),
        ]);
    }
}
