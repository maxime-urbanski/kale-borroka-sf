<?php

declare(strict_types=1);

namespace App\Controller\Catalog;

use App\Catalog\CatalogFilterReader;
use App\Catalog\CatalogFilters;
use App\Catalog\CatalogQuery;
use App\Enum\SupportType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\EnumRequirement;

/**
 * Number of results for the filters of the query string: the « Voir N résultats » of the
 * mobile filter panel, updated as boxes are ticked (result-count_controller.js).
 */
#[AsController]
class CatalogCountController
{
    #[Route(
        path: '/catalog/count/{support}',
        name: 'app_catalog_count',
        requirements: ['support' => new EnumRequirement(SupportType::class)],
        defaults: ['support' => null],
        methods: [Request::METHOD_GET],
        priority: 3,
    )]
    #[Route(
        path: '/production/count',
        name: 'app_production_count',
        defaults: ['productions' => true],
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(
        Request $request,
        CatalogFilterReader $reader,
        CatalogQuery $catalogQuery,
        ?SupportType $support = null,
        bool $productions = false,
    ): JsonResponse {
        return new JsonResponse(['count' => $catalogQuery->count($reader->read($request, CatalogFilters::of($support, $productions)))]);
    }
}
