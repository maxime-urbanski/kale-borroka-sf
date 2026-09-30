<?php

declare(strict_types=1);

namespace App\Service;

use App\Routing\PageMatcher;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Builds the breadcrumb from the current path: one entry per prefix that is a page, the
 * page-N segments of the pagination skipped. A prefix that is no page is left out.
 */
readonly class BreadcrumbService implements BreadcrumbInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private PageMatcher $pageMatcher,
    ) {
    }

    /**
     * @param string|null $lastItemName name of the current page (an article's title), if it is in the trail
     *
     * @return list<array{name: string, path: string, parameters: array<string, mixed>}>
     */
    public function breadcrumb(?string $lastItemName = null): array
    {
        // The path only: a "/" in the query string is no segment.
        $pathInfo = (string) $this->requestStack->getMainRequest()?->getPathInfo();

        $prefixes = ['/'];
        $prefix = '';
        foreach (explode('/', $pathInfo) as $segment) {
            if ('' === $segment || str_starts_with($segment, 'page-')) {
                continue;
            }

            $prefix .= '/'.$segment;
            $prefixes[] = $prefix;
        }

        $breadcrumb = [];
        $currentPageIsLast = false;

        foreach ($prefixes as $path) {
            $match = $this->pageMatcher->match($path);

            if (null === $match) {
                continue;
            }

            $route = (string) $match['_route'];
            unset($match['_route']);
            /* @var array<string, mixed> $match route parameters, by name */

            $breadcrumb[] = [
                'name' => '/' === $path ? 'Home' : basename($path),
                'path' => $route,
                'parameters' => $match,
            ];
            $currentPageIsLast = $path === end($prefixes);
        }

        // Only the current page gets the name: never a parent left last because the
        // current path is no page.
        if (null !== $lastItemName && $currentPageIsLast && null !== $current = array_pop($breadcrumb)) {
            $current['name'] = $lastItemName;
            $breadcrumb[] = $current;
        }

        return $breadcrumb;
    }
}
