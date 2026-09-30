<?php

declare(strict_types=1);

namespace App\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Matches a path to the page it shows, as a visitor's browser would request it: with GET.
 * The router matches with the method of the request being handled, so from a POST every
 * GET-only page threw MethodNotAllowedException. Null when the path is none of our pages.
 */
final readonly class PageMatcher
{
    public function __construct(
        private RouterInterface $router,
    ) {
    }

    /**
     * @return array<string, mixed>|null the route attributes, _route included
     */
    public function match(string $path): ?array
    {
        $context = $this->router->getContext();
        $method = $context->getMethod();
        $context->setMethod(Request::METHOD_GET);

        try {
            return $this->router->match($path);
        } catch (ExceptionInterface) {
            return null;
        } finally {
            $context->setMethod($method);
        }
    }
}
