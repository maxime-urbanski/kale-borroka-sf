<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ExceptionInterface;
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
    ) {
    }

    public function getReferer(): string
    {
        $routeReferer = (string) $this->requestStack->getCurrentRequest()?->headers->get('referer');
        $refererPathInfo = Request::create($routeReferer)->getPathInfo();

        // The actions calling this are POST, and the router matches with the current
        // request's method: match the page as the GET it was, or every GET-only route
        // throws MethodNotAllowedException. A path that is none of our pages goes home.
        $context = $this->router->getContext();
        $method = $context->getMethod();
        $context->setMethod(Request::METHOD_GET);

        try {
            $routeMatch = $this->router->match($refererPathInfo);
        } catch (ExceptionInterface) {
            return $this->router->generate('app_homepage');
        } finally {
            $context->setMethod($method);
        }

        $routeName = $routeMatch['_route'] ?? 'app_homepage';

        unset($routeMatch['_route'], $routeMatch['_controller']);

        return $this->router->generate($routeName, $routeMatch);
    }
}
