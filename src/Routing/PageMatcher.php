<?php

declare(strict_types=1);

namespace App\Routing;

use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Matches a path to the page it shows, as a visitor's browser would request it: with GET.
 * The router matches with the method of the request being handled, so from a POST every
 * GET-only page threw MethodNotAllowedException. Null when the path is none of our pages.
 *
 * Returns the route name (_route) and its parameters only, ready for generate(): not the
 * controller, nor the attributes of a trailing-slash redirect, which GET matching allows.
 */
final readonly class PageMatcher
{
    public function __construct(
        private RouterInterface $router,
    ) {
    }

    private const array REDIRECT_ATTRIBUTES = ['path', 'permanent', 'scheme', 'httpPort', 'httpsPort', 'keepQueryParams', 'keepRequestMethod', 'ignoreAttributes'];

    /**
     * @return array<string, mixed>|null _route and the route parameters
     */
    public function match(string $path): ?array
    {
        $context = $this->router->getContext();
        $method = $context->getMethod();
        $context->setMethod(Request::METHOD_GET);

        try {
            $match = $this->router->match($path);
        } catch (ExceptionInterface) {
            return null;
        } finally {
            $context->setMethod($method);
        }

        if (RedirectController::class.'::urlRedirectAction' === ($match['_controller'] ?? null)) {
            $match = array_diff_key($match, array_flip(self::REDIRECT_ATTRIBUTES));
        }

        unset($match['_controller']);

        return $match;
    }
}
