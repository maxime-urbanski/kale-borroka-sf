<?php

declare(strict_types=1);

namespace App\Controller\Catalog;

use App\Data\ArticleFilterData;
use App\Entity\Support;
use App\Enum\SupportType;
use App\Form\ArticleFilterFormType;
use App\Repository\AlbumRepository;
use App\Service\BreadcrumbInterface;
use App\Service\CustomPaginationInterface;
use App\Service\DispatchFilterValueInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\EnumRequirement;
use Symfony\Component\Routing\Requirement\Requirement;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
class CatalogBySupportController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    #[Route(
        path: '/catalog/{support}/{page}',
        name: 'app_catalog_list',
        requirements: [
            'support' => new EnumRequirement(SupportType::class),
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
        AlbumRepository $albumRepository,
        #[MapEntity(mapping: ['support' => 'name'])]
        Support $support,
        string $page,
    ): Response {
        $filters = new ArticleFilterData();
        $filters->supports[] = $support;

        $form = $formInterface->create(ArticleFilterFormType::class, $filters);
        $form->handleRequest($request);

        $albums = $albumRepository->filterAlbumQuery(
            $dispatchFilterValue->dispatchFilterValue($filters)
        );
        $pagination = $customPagination->pagination($albums, $page, 12);

        unset($filters->globalFilters);

        $content = $twig->render('catalog/articles.html.twig', [
            'albums' => $pagination,
            'supportScope' => $support,
            'breadcrumb' => $breadcrumb->breadcrumb(),
            'form' => $form->createView(),
            'filters' => $filters,
        ]);

        return new Response($content);
    }
}
