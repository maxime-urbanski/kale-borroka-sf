<?php

declare(strict_types=1);

namespace App\Service;

use App\Routing\PageMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * Where to send the visitor back after an action: the page they came from, rebuilt from
 * our own routes (only the path of the Referer is used, so it never leads off-site).
 */
readonly class RefererService implements RefererInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private RouterInterface $router,
        private PageMatcher $pageMatcher,
    ) {
    }

    public function getReferer(): string
    {
        $routeReferer = (string) $this->requestStack->getCurrentRequest()?->headers->get('referer');
        $refererPathInfo = Request::create($routeReferer)->getPathInfo();

        // The actions calling this are POST: PageMatcher matches the page as the GET it was.
        // A path that is none of our pages goes home.
        $routeMatch = $this->pageMatcher->match($refererPathInfo);

        if (null === $routeMatch) {
            return $this->router->generate('app_homepage');
        }

        $routeName = $routeMatch['_route'] ?? 'app_homepage';

        unset($routeMatch['_route']);

        return $this->router->generate($routeName, $routeMatch);
    }
}
