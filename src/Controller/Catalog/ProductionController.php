<?php

declare(strict_types=1);

namespace App\Controller\Catalog;

use App\Data\ArticleFilterData;
use App\Form\ProductionFilterFormType;
use App\Repository\ArticleRepository;
use App\Service\BreadcrumbInterface;
use App\Service\CustomPaginationService;
use App\Service\DispatchFilterValueService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductionController extends AbstractController
{
    #[Route('/production/{page}', 'app_production', requirements: ['page' => '^(page-)\d+'])]
    public function index(
        ArticleRepository $articleRepository,
        CustomPaginationService $paginationService,
        DispatchFilterValueService $dispatchFilterValueService,
        Request $request,
        BreadcrumbInterface $breadcrumb,
        string $page = 'page-1'
    ): Response {
        $filters = new ArticleFilterData();
        $filters->globalFilters['kbrProduction'] = true;

        $productionForm = $this->createForm(ProductionFilterFormType::class, $filters);
        $productionForm->handleRequest($request);

        $productions = $articleRepository
            ->filterArticleQuery($dispatchFilterValueService->dispatchFilterValue($filters));
        $pagination = $paginationService->pagination($productions, $page);

        return $this->render('catalog/articles.html.twig', [
            'articles' => $pagination,
            'breadcrumb' => $breadcrumb->breadcrumb(),
            'form' => $productionForm,
            'filters' => $filters,
        ]);
    }
}
