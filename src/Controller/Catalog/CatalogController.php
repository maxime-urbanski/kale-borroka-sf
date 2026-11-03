<?php

declare(strict_types=1);

namespace App\Controller\Catalog;

use App\Catalog\CatalogFilters;
use App\Catalog\CatalogPage;
use App\Enum\SupportType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\EnumRequirement;

/**
 * The catalogue: every section at /catalog (« Tout »), one at /catalog/{support}. See
 * CatalogPage.
 */
#[AsController]
class CatalogController
{
    #[Route(
        path: '/catalog/{page}',
        name: 'app_catalog',
        requirements: ['page' => 'page-\d+'],
        defaults: ['page' => null],
        methods: [Request::METHOD_GET],
    )]
    #[Route(
        path: '/catalog/{support}/{page}',
        name: 'app_catalog_list',
        requirements: ['support' => new EnumRequirement(SupportType::class), 'page' => 'page-\d+'],
        defaults: ['page' => null],
        methods: [Request::METHOD_GET],
        priority: 2,
    )]
    public function __invoke(
        Request $request,
        CatalogPage $catalogPage,
        // No PHP default: the route loader would make {support} optional on app_catalog_list.
        ?SupportType $support,
        ?string $page,
    ): Response {
        return $catalogPage->render($request, CatalogFilters::of($support), $page);
    }
}
