<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\BreadcrumbInterface;
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/**
 * The breadcrumb matches each prefix of the URL to its route. It must not depend on the
 * method of the request being handled: parent pages are GET-only, and matching them as a
 * POST threw MethodNotAllowedException.
 */
class BreadcrumbDuringPostTest extends KernelTestCase
{
    use ServiceTrait;

    public function testBuiltWhileHandlingAPost(): void
    {
        $breadcrumb = $this->breadcrumbFor(Request::create('/catalog/lp', 'POST'));

        self::assertSame(['Home', 'catalog', 'lp'], array_column($breadcrumb, 'name'));
        self::assertSame(['app_homepage', 'app_catalog', 'app_catalog_list'], array_column($breadcrumb, 'path'));
        self::assertSame('POST', self::service(RouterInterface::class)->getContext()->getMethod(), 'the router context is left as it was');
    }

    public function testASegmentThatIsNoPageIsSkipped(): void
    {
        // /mon-compte/nowhere: /mon-compte is a page, /mon-compte/nowhere is not.
        $breadcrumb = $this->breadcrumbFor(Request::create('/mon-compte/nowhere'));

        self::assertSame(['app_homepage', 'app_user_informations'], array_column($breadcrumb, 'path'));
    }

    /**
     * The article page accepts POST (its add-to-cart form): its trail ends with the article.
     */
    public function testTheArticlePageDuringItsPost(): void
    {
        $breadcrumb = $this->breadcrumbFor(Request::create('/catalog/lp/some-album', 'POST'), 'Some Album');

        self::assertSame(['Home', 'catalog', 'lp', 'Some Album'], array_column($breadcrumb, 'name'));
        self::assertSame('app_catalog_show', $breadcrumb[3]['path']);
    }

    /**
     * A "/" in the query string is no path segment.
     */
    public function testAQueryStringWithASlash(): void
    {
        // Raw, as a browser sends it: Request::create() would encode the slashes.
        $request = Request::create('/catalog/lp');
        $request->server->set('REQUEST_URI', '/catalog/lp?ref=/a/b');
        // It used to only raise "Array to string conversion": a 500 in debug.
        set_error_handler(static fn (int $severity, string $message): never => throw new \ErrorException($message, 0, $severity));

        try {
            $breadcrumb = $this->breadcrumbFor($request);
        } finally {
            restore_error_handler();
        }

        self::assertSame(['app_homepage', 'app_catalog', 'app_catalog_list'], array_column($breadcrumb, 'path'));
    }

    /**
     * The last name only replaces the current page, never a parent left last because the
     * current path is no page.
     */
    public function testTheLastNameOnlyReplacesTheCurrentPage(): void
    {
        $breadcrumb = $this->breadcrumbFor(Request::create('/mon-compte/nowhere'), 'Nowhere');

        self::assertSame(['Home', 'mon-compte'], array_column($breadcrumb, 'name'));
    }

    /**
     * The trail of each catalog page: a level that stops resolving to a page (a renamed
     * route) would silently disappear otherwise.
     *
     * @param list<string> $routes
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('trails')]
    public function testTrails(string $uri, array $routes): void
    {
        self::assertSame($routes, array_column($this->breadcrumbFor(Request::create($uri)), 'path'));
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function trails(): iterable
    {
        yield 'catalog' => ['/catalog', ['app_homepage', 'app_catalog']];
        yield 'support' => ['/catalog/lp', ['app_homepage', 'app_catalog', 'app_catalog_list']];
        yield 'support, page 2' => ['/catalog/lp/page-2', ['app_homepage', 'app_catalog', 'app_catalog_list']];
        yield 'article' => ['/catalog/lp/some-album', ['app_homepage', 'app_catalog', 'app_catalog_list', 'app_catalog_show']];
        yield 'productions' => ['/production', ['app_homepage', 'app_production']];
    }

    /**
     * @return array<int, array{name: string, path: string, parameters: array<mixed>}>
     */
    private function breadcrumbFor(Request $request, ?string $lastItemName = null): array
    {
        self::bootKernel();
        $container = self::getContainer();
        self::service(RequestStack::class)->push($request);
        self::service(RouterInterface::class)->setContext((new RequestContext())->fromRequest($request));

        return self::service(BreadcrumbInterface::class)->breadcrumb($lastItemName);
    }
}
