<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use function Symfony\Component\String\u;


final readonly class RedirectRefererService
{
    public function __construct(private RouterInterface $router)
    {
    }

    public function getRefererRoute(Request $request): string
    {
        $referer = $request->headers->get('referer');
        $refererStr = u($referer);

        if ($refererStr->isEmpty()) {
            echo 'Un problèmes est survenu';
        }

        $getPathInfo = Request::create($referer)->getPathInfo();

        $routeInfo = $this->router->match($getPathInfo);

        $refererRoute = $routeInfo['_route'] ?? '';

        if (!$refererRoute) {
            echo 'Un problèmes est survenu';
        }

        unset($routeInfo['_controller']);

        return $getPathInfo;
    }
}

