<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

readonly class BreadcrumbService implements BreadcrumbInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private RouterInterface $router
    ) {
    }

    public function breadcrumb(?string $lastItemName = null): array
    {
        $breadcrumb = [
            [
                'name' => 'Accueil',
                'path' => 'app_homepage',
            ],
        ];

        $url = $this->requestStack->getMainRequest()->getRequestUri();

        $splitUrl = \explode('/', $url);
        array_shift($splitUrl);

        $uri = '/';
        foreach ($splitUrl as $value) {
            $uri = $uri.$value.'/';
            $uriWithoutLastSlash = substr($uri, 0, -1);

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

        if ($lastItemName) {
            $totalItem = \count($breadcrumb);
            $breadcrumb[$totalItem - 1]['name'] = $lastItemName;
        }

        return $breadcrumb;
    }
}
