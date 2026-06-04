<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\BreadcrumbService;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class BreadcrumbServiceTest extends KernelTestCase
{
    public const BREADCRUMB_URI = '/catalog/lp';
    public const BREADCRUMB_URI_PAGINATION = '/catalog/lp/page-2';
    public const BREADCRUMB_URI_QUERY = '/catalog/lp?globalFilters[artists][]=1';
    public const BREADCRUMB_ARRAY_LENGTH_THREE = 3;
    public const BREADCRUMB_LAST_ITEM_NAME = 'last-item-name';

    public function testCountBreadcrumbItem(): void
    {
        self::countBreadCrumbItem(
            self::BREADCRUMB_URI,
            self::BREADCRUMB_ARRAY_LENGTH_THREE
        );
    }

    public function testCountBreadcrumbItemWithPagination(): void
    {
        self::countBreadCrumbItem(
            self::BREADCRUMB_URI_PAGINATION,
            self::BREADCRUMB_ARRAY_LENGTH_THREE
        );
    }

    public function testCountBreadcrumbItemWithQuery(): void
    {
        self::countBreadCrumbItem(
            self::BREADCRUMB_URI_QUERY,
            self::BREADCRUMB_ARRAY_LENGTH_THREE
        );
    }

    public function testGivenLastItemName(): void
    {
        $requestStack = self::mockRequestStack(self::BREADCRUMB_URI);
        $router = self::mockRouterMatch();

        $breadcrumbService = new BreadcrumbService($requestStack, $router);
        $breadcrumbWithLastNameItem = $breadcrumbService->breadcrumb(
            self::BREADCRUMB_LAST_ITEM_NAME
        );

        self::assertEquals(
            self::BREADCRUMB_LAST_ITEM_NAME,
            $breadcrumbWithLastNameItem[self::BREADCRUMB_ARRAY_LENGTH_THREE - 1]['name']
        );
    }

    public function testNotGivenLastItemName(): void
    {
        $requestStack = self::mockRequestStack(self::BREADCRUMB_URI);
        $router = self::mockRouterMatch();

        $breadcrumbService = new BreadcrumbService($requestStack, $router);
        $breadcrumbWithLastNameItem = $breadcrumbService->breadcrumb();

        self::assertNotSame(
            self::BREADCRUMB_LAST_ITEM_NAME,
            $breadcrumbWithLastNameItem[self::BREADCRUMB_ARRAY_LENGTH_THREE - 1]['name']
        );
    }

    private function createTestRequest(string $uri): Request
    {
        return Request::create($uri);
    }

    private function mockRouterMatch(): RouterInterface|MockObject
    {
        $router = self::createMock(RouterInterface::class);

        $router->method('match')->willReturnCallback(function ($uri) {
            $routes = [
                '/' => [
                    'name' => '',
                    '_route' => 'app_homepage',
                ],
                '/catalog' => [
                    'name' => 'catalog',
                    '_route' => 'app_catalog',
                ],
                '/catalog/lp' => [
                    'name' => 'catalog.lp',
                    '_route' => 'app_catalog_list',
                    'page' => 'page-1',
                    'support' => 'lp',
                ],
            ];

            return $routes[$uri] ?? [];
        });

        return $router;
    }

    /**
     * @throws Exception
     */
    private function mockRequestStack(string $uri): RequestStack|MockObject
    {
        $requestStack = self::createMock(RequestStack::class);
        $requestStack
            ->method('getMainRequest')
            ->willReturn(self::createTestRequest($uri)
            );

        return $requestStack;
    }

    private function countBreadCrumbItem(string $uri, int $arrayLength): void
    {
        $requestStack = self::mockRequestStack($uri);
        $router = self::mockRouterMatch();

        $breadcrumbService = new BreadcrumbService($requestStack, $router);

        self::assertNotEmpty($breadcrumbService->breadcrumb());
        self::assertCount(
            $arrayLength,
            $breadcrumbService->breadcrumb()
        );
    }
}
