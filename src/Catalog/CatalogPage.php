<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Enum\SupportType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

/**
 * The catalogue page, for /catalog, /catalog/{support} and /production (CatalogFilters::of()
 * says which): filters in the query string (CatalogFilterReader), the page in the path. The
 * first page has no segment (/page-1 redirects), a page past the last one is a 404.
 */
final readonly class CatalogPage
{
    public const int PER_PAGE = 12;

    public function __construct(
        private Environment $twig,
        private CatalogFilterReader $reader,
        private PaginatorInterface $paginator,
        private CatalogQuery $catalogQuery,
        private FacetCounter $facetCounter,
        private CatalogLinks $links,
    ) {
    }

    /**
     * @param string|null $page the {page} segment ("page-N"), null for the first page
     */
    public function render(Request $request, CatalogFilters $base, ?string $page): Response
    {
        $pageUrl = $this->links->url($base);
        $filters = $this->reader->read($request, $base);

        // The filter form sends its empty fields too (?artistSearch=&sort=new), and page 1 has
        // no segment: back to the short URL, the one the pills and the pages link to.
        $query = $request->query->all();
        $short = array_filter($query, static fn (mixed $value, string|int $key): bool => '' !== $value
            && !('sort' === $key && CatalogSort::NEW->value === $value)
            // On « Nos productions » the switch is implied: not a second URL for the page.
            && !($base->productions && CatalogFacet::KBR_PRODUCTION->value === $key), \ARRAY_FILTER_USE_BOTH);
        $pageNumber = null === $page ? 1 : (int) substr($page, \strlen('page-'));
        $firstPage = null !== $page && $pageNumber < 2;
        if ($short !== $query || $firstPage) {
            $path = $firstPage ? $pageUrl : strtok((string) $request->getRequestUri(), '?');

            return new RedirectResponse($path.([] !== $short ? '?'.http_build_query($short) : ''), $firstPage ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND);
        }

        // Checked before paginating: KnpPaginator fails on an offset past PHP_INT_MAX.
        $lastPage = max(1, (int) ceil($this->catalogQuery->count($filters) / self::PER_PAGE));
        if ($pageNumber > $lastPage) {
            throw new NotFoundHttpException('Cette page du catalogue n\'existe pas.');
        }

        $articles = $this->paginator->paginate($this->catalogQuery->articles($filters), $pageNumber, self::PER_PAGE, [
            // Every article is a single row (see CatalogQuery): no DISTINCT needed.
            PaginatorInterface::DISTINCT => false,
            // ?sort= is ours (CatalogSort), not KnpPaginator's; no filtering through the URL.
            PaginatorInterface::SORT_FIELD_PARAMETER_NAME => null,
            PaginatorInterface::FILTER_FIELD_PARAMETER_NAME => null,
        ]);
        $this->catalogQuery->prime($articles);

        $pills = $this->links->pills($filters);

        return new Response($this->twig->render('catalog/index.html.twig', [
            'section' => $filters->section,
            'productions' => $filters->productions,
            'filters' => $filters,
            'articles' => $articles,
            'facets' => $this->facetCounter->facets($filters),
            'pills' => $pills,
            'clear_url' => $this->links->cleared($filters),
            'best_drop' => 0 === $articles->getTotalItemCount() ? $this->bestDrop($pills) : null,
            'action' => $pageUrl,
            'tabs' => $this->tabs(),
            'sorts' => CatalogSort::cases(),
            'count_url' => $this->links->countUrl($filters),
        ]));
    }

    /**
     * When nothing matches: the filter whose removal brings back the most results.
     *
     * @param list<CatalogPill> $pills
     *
     * @return array{pill: CatalogPill, count: int}|null
     */
    private function bestDrop(array $pills): ?array
    {
        $best = null;
        foreach ($pills as $pill) {
            $count = $this->catalogQuery->count($pill->without);
            if ($count > 0 && (null === $best || $count > $best['count'])) {
                $best = ['pill' => $pill, 'count' => $count];
            }
        }

        return $best;
    }

    /**
     * @return list<array{section: SupportType|null, label: string, url: string}>
     */
    private function tabs(): array
    {
        $tabs = [['section' => null, 'label' => 'Tout', 'url' => $this->links->url(new CatalogFilters())]];
        foreach (SupportType::cases() as $section) {
            $tabs[] = ['section' => $section, 'label' => $section->label(), 'url' => $this->links->url(CatalogFilters::of($section))];
        }

        return $tabs;
    }
}
