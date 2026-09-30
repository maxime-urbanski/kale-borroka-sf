<?php

declare(strict_types=1);

namespace App\Tests\Controller\Catalog;

use App\Tests\Order\OrderTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The {page} segment is "page-N" for any N of digits: page-0 (or a hand-typed page-00)
 * shows the first page instead of KnpPaginator's exception.
 */
class PaginationTest extends WebTestCase
{
    use OrderTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->beginIsolation();
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function pages(): iterable
    {
        yield 'catalog' => ['/catalog/lp/page-0'];
        yield 'productions' => ['/production/page-0'];
        yield 'wishlist' => ['/mon-compte/wishlist/page-0'];
        yield 'collection' => ['/mon-compte/collection/page-0'];
        yield 'leading zeros' => ['/production/page-00'];
    }

    #[DataProvider('pages')]
    public function testPageZeroShowsTheFirstPage(string $uri): void
    {
        $this->client->loginUser($this->user('test@test.fr'));
        $this->client->request('GET', $uri);

        self::assertResponseIsSuccessful();
    }
}
