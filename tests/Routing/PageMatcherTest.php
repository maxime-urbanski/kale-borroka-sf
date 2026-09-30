<?php

declare(strict_types=1);

namespace App\Tests\Routing;

use App\Routing\PageMatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class PageMatcherTest extends TestCase
{
    /**
     * Matched as a GET, a path missing its trailing slash comes back as a redirect to the
     * route: only the route and its own parameters are kept, not the redirect's.
     */
    public function testARedirectMatchKeepsOnlyTheRoute(): void
    {
        $router = $this->createStub(RouterInterface::class);
        $router->method('getContext')->willReturn(new RequestContext(method: 'POST'));
        $router->method('match')->willReturn([
            '_controller' => RedirectController::class.'::urlRedirectAction',
            'path' => '/catalog/',
            'permanent' => true,
            'scheme' => null,
            'httpPort' => 80,
            'httpsPort' => 443,
            '_route' => 'app_catalog',
            'page' => 'page-1',
        ]);

        self::assertSame(['_route' => 'app_catalog', 'page' => 'page-1'], (new PageMatcher($router))->match('/catalog'));
        self::assertSame('POST', $router->getContext()->getMethod());
    }
}
