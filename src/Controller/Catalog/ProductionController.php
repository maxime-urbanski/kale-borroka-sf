<?php

declare(strict_types=1);

namespace App\Controller\Catalog;

use App\Catalog\CatalogFilters;
use App\Catalog\CatalogPage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * « Nos productions »: the catalogue page with the label's own productions only (KBR#), no
 * section tabs nor « Nos productions » switch. See CatalogPage.
 */
#[AsController]
class ProductionController
{
    #[Route(
        path: '/production/{page}',
        name: 'app_production',
        requirements: ['page' => 'page-\d+'],
        defaults: ['page' => null],
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(Request $request, CatalogPage $catalogPage, ?string $page): Response
    {
        return $catalogPage->render($request, CatalogFilters::of(null, productions: true), $page);
    }
}
