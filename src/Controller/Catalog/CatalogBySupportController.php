<?php

declare(strict_types=1);

namespace App\Controller\Catalog;

use App\Data\ArticleFilterData;
use App\Entity\Support;
use App\Form\ArticleFilterFormType;
use App\Repository\ArticleRepository;
use App\Service\BreadcrumbInterface;
use App\Service\CustomPaginationInterface;
use App\Service\DispatchFilterValueInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
class CatalogBySupportController
{
    public const SUPPORT_REQUIREMENTS = 'lp|ep|tape|fanzine|cd';

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    #[Route(
        path: '/catalog/{support}/{page}',
        name: 'app_catalog_list',
        requirements: [
            'support' => self::SUPPORT_REQUIREMENTS,
            'page' => '^(page-)'.Requirement::DIGITS,
        ],
        defaults: ['page' => 'page-1'],
        methods: Request::METHOD_GET, priority: 2
    )]
    public function __invoke(
        Request $request,
        Environment $twig,
        FormFactoryInterface $formInterface,
        BreadcrumbInterface $breadcrumb,
        DispatchFilterValueInterface $dispatchFilterValue,
        CustomPaginationInterface $customPagination,
        ArticleRepository $articleRepository,
        #[MapEntity(mapping: ['support' => 'name'])]
        Support $support,
        string $page,
    ): Response {
        $filters = new ArticleFilterData();
        $filters->supports[] = $support;

        $form = $formInterface->create(ArticleFilterFormType::class, $filters);
        $form->handleRequest($request);

        $articles = $articleRepository->filterArticleQuery(
            $dispatchFilterValue->dispatchFilterValue($filters)
        );
        $pagination = $customPagination->pagination($articles, $page, 12);

        unset($filters->globalFilters);

        $content = $twig->render('catalog/articles.html.twig', [
            'articles' => $pagination,
            'breadcrumb' => $breadcrumb->breadcrumb(),
            'form' => $form->createView(),
            'filters' => $filters,
        ]);

        return new Response($content);
    }
}
