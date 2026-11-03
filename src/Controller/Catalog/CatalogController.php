<?php

declare(strict_types=1);

namespace App\Controller\Catalog;

use App\Catalog\CatalogFilters;
use App\Catalog\CatalogLinks;
use App\Catalog\CatalogPill;
use App\Catalog\CatalogQuery;
use App\Catalog\CatalogSort;
use App\Catalog\FacetCounter;
use App\Enum\SupportType;
use App\Form\CatalogFilterType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\EnumRequirement;
use Twig\Environment;

/**
 * The catalogue: every section at /catalog (« Tout »), one at /catalog/{support}. Filters
 * in the query string (CatalogFilterType), the page in the path; the first page has no
 * segment (/page-1 redirects), a page past the last one is a 404.
 */
#[AsController]
class CatalogController
{
    public const int PER_PAGE = 12;

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
        Environment $twig,
        FormFactoryInterface $formFactory,
        PaginatorInterface $paginator,
        CatalogQuery $catalogQuery,
        FacetCounter $facetCounter,
        CatalogLinks $links,
        // No PHP default: the route loader would make {support} optional on app_catalog_list.
        ?SupportType $support,
        ?string $page,
    ): Response {
        $filters = self::filters($request, $formFactory, $support);

        // The filter form sends its empty fields too (?artistSearch=&sort=new), and page 1 has
        // no segment: back to the short URL, the one the pills and the pages link to.
        $query = $request->query->all();
        $short = array_filter($query, static fn (mixed $value, string|int $key): bool => '' !== $value && !('sort' === $key && CatalogSort::NEW->value === $value), \ARRAY_FILTER_USE_BOTH);
        $pageNumber = null === $page ? 1 : (int) substr($page, \strlen('page-'));
        if ($short !== $query || (null !== $page && $pageNumber < 2)) {
            $path = $pageNumber < 2 ? $links->url(CatalogFilters::of($support)) : strtok((string) $request->getRequestUri(), '?');

            return new RedirectResponse($path.([] !== $short ? '?'.http_build_query($short) : ''), null !== $page && $pageNumber < 2 ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND);
        }

        // Checked before paginating: KnpPaginator fails on an offset past PHP_INT_MAX.
        $lastPage = max(1, (int) ceil($catalogQuery->count($filters) / self::PER_PAGE));
        if ($pageNumber > $lastPage) {
            throw new NotFoundHttpException('Cette page du catalogue n\'existe pas.');
        }

        $articles = $paginator->paginate($catalogQuery->articles($filters), $pageNumber, self::PER_PAGE, [
            // Every article is a single row (see CatalogQuery): no DISTINCT needed.
            PaginatorInterface::DISTINCT => false,
            // ?sort= is ours (CatalogSort), not KnpPaginator's; no filtering through the URL.
            PaginatorInterface::SORT_FIELD_PARAMETER_NAME => null,
            PaginatorInterface::FILTER_FIELD_PARAMETER_NAME => null,
        ]);
        $catalogQuery->prime($articles);

        $pills = $links->pills($filters);

        return new Response($twig->render('catalog/index.html.twig', [
            'section' => $support,
            'filters' => $filters,
            'articles' => $articles,
            'facets' => $facetCounter->facets($filters),
            'pills' => $pills,
            'clear_url' => $links->cleared($filters),
            'best_drop' => 0 === $articles->getTotalItemCount() ? self::bestDrop($pills, $catalogQuery) : null,
            'action' => $links->url(CatalogFilters::of($support)),
            'tabs' => self::tabs($links),
            'sorts' => CatalogSort::cases(),
            'count_url' => $links->countUrl($support),
        ]));
    }

    /**
     * The filters of the query string; a value the form does not know is left out.
     */
    public static function filters(Request $request, FormFactoryInterface $formFactory, ?SupportType $section): CatalogFilters
    {
        $filters = CatalogFilters::of($section);
        // Not isValid(): a filter form has nothing to refuse, an unknown value is dropped
        // by the form and the others still apply. GET, no CSRF token.
        $formFactory->create(CatalogFilterType::class, $filters)->handleRequest($request);

        return $filters;
    }

    /**
     * When nothing matches: the filter whose removal brings back the most results.
     *
     * @param list<CatalogPill> $pills
     *
     * @return array{pill: CatalogPill, count: int}|null
     */
    private static function bestDrop(array $pills, CatalogQuery $catalogQuery): ?array
    {
        $best = null;
        foreach ($pills as $pill) {
            $count = $catalogQuery->count($pill->without);
            if ($count > 0 && (null === $best || $count > $best['count'])) {
                $best = ['pill' => $pill, 'count' => $count];
            }
        }

        return $best;
    }

    /**
     * @return list<array{section: SupportType|null, label: string, url: string}>
     */
    private static function tabs(CatalogLinks $links): array
    {
        $tabs = [['section' => null, 'label' => 'Tout', 'url' => $links->url(new CatalogFilters())]];
        foreach (SupportType::cases() as $section) {
            $tabs[] = ['section' => $section, 'label' => $section->label(), 'url' => $links->url(CatalogFilters::of($section))];
        }

        return $tabs;
    }
}
