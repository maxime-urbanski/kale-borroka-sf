<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

readonly class RefererService implements RefererInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private RouterInterface $router
    ) {
    }

    public function getReferer(): string
    {
        $routeReferer = (string) $this->requestStack->getCurrentRequest()->headers->get('referer');
        $refererPathInfo = Request::create($routeReferer)->getPathInfo();
        $routeMatch = $this->router->match($refererPathInfo);

        if (!$routeMatch) {
            return '';
        }

        $routeName = $routeMatch['_route'] ?? 'app_homepage';

        unset($routeMatch['_route'], $routeMatch['_controller']);

        return $this->router->generate($routeName, $routeMatch);
    }
}
