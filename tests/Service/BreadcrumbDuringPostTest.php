<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\BreadcrumbInterface;
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
    public function testBuiltWhileHandlingAPost(): void
    {
        $breadcrumb = $this->breadcrumbFor(Request::create('/catalog/lp', 'POST'));

        self::assertSame(['Home', 'catalog', 'lp'], array_column($breadcrumb, 'name'));
        self::assertSame(['app_homepage', 'app_catalog', 'app_catalog_list'], array_column($breadcrumb, 'path'));
        self::assertSame('POST', self::getContainer()->get(RouterInterface::class)->getContext()->getMethod(), 'the router context is left as it was');
    }

    public function testASegmentThatIsNoPageIsSkipped(): void
    {
        // /mon-compte/nowhere: /mon-compte is a page, /mon-compte/nowhere is not.
        $breadcrumb = $this->breadcrumbFor(Request::create('/mon-compte/nowhere'));

        self::assertSame(['app_homepage', 'app_user_informations'], array_column($breadcrumb, 'path'));
    }

    /**
     * @return array<int, array{name: string, path: string, parameters: array<mixed>}>
     */
    private function breadcrumbFor(Request $request): array
    {
        self::bootKernel();
        $container = self::getContainer();
        $container->get(RequestStack::class)->push($request);
        $container->get(RouterInterface::class)->setContext((new RequestContext())->fromRequest($request));

        return $container->get(BreadcrumbInterface::class)->breadcrumb();
    }
}
