<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

readonly class BreadcrumbService implements BreadcrumbInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private RouterInterface $router,
    ) {
    }

    /**
     * @return array<int, array{name: string, path: string, paramater: array<mixed>}>
     */
    public function breadcrumb(?string $lastItemName = null): array
    {
        /** @var array<int, array{name: string, path: string, paramater: array<mixed>}> $breadcrumb */
        $breadcrumb = [];
        $url = $this->requestStack->getMainRequest()->getRequestUri();
        $splitUrl = \explode('/', $url);
        $uri = '';

        foreach ($splitUrl as $value) {
            if (!str_starts_with($value, 'page-')) {
                $uri .= $value.'/';
                $uriWithoutLastSlash = strlen($uri) > 1 ? rtrim($uri, '/') : $uri;

                if (str_contains($uriWithoutLastSlash, '?')) {
                    $uri = \explode('?', $uriWithoutLastSlash);
                    $uriWithoutLastSlash = $uri[0];
                }

                $match = $this->router->match($uriWithoutLastSlash);
                unset($match['_controller']);

                if ($match) {
                    $routeName = $match['_route'];
                    unset($match['_route']);
                    $breadcrumb[] = [
                        'name' => 'breadcrumb'.\str_replace('/', '.', $uriWithoutLastSlash),
                        'path' => $routeName,
                        'parameters' => $match,
                    ];
                }
            }
        }

        if ($lastItemName) {
            $totalItem = \count($breadcrumb);
            $breadcrumb[$totalItem - 1]['name'] = $lastItemName;
        }

        return $breadcrumb;
    }
}
