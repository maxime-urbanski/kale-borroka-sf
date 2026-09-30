<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Article;
use App\Tests\Order\OrderTestTrait;
use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The navbar reads the cart on every page: its articles are loaded in one query, however
 * many lines the cart has.
 */
class CartQueriesTest extends WebTestCase
{
    use OrderTestTrait;

    private ?KernelBrowser $client = null;

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

    public function testMoreLinesDoNotMeanMoreQueries(): void
    {
        $releases = $this->releasesWithStock(5, 5, 5, 5);

        $this->addToCart($releases[0]);
        $oneLine = $this->queriesOf('/');

        foreach (\array_slice($releases, 1) as $release) {
            $this->addToCart($release);
        }
        $fourLines = $this->queriesOf('/');

        self::assertSame($oneLine, $fourLines);
    }

    public function testTheCartPageReadsTheCartOncePerComponent(): void
    {
        [$release] = $this->releasesWithStock(5);
        $this->addToCart($release);
        $this->queriesOf('/cart');

        $collector = $this->client->getProfile()->getCollector('db');
        self::assertInstanceOf(DoctrineDataCollector::class, $collector);
        $articleQueries = array_filter(
            $collector->getQueries()['default'] ?? [],
            static fn (array $query): bool => str_contains($query['sql'], 'FROM article'),
        );

        // The navbar's and the page's; the total reuses the page's lines.
        self::assertCount(2, $articleQueries);
    }

    public function testTheCartIsStillFilteredAndCapped(): void
    {
        [$kept, $unpublished, $capped] = $this->releasesWithStock(5, 5, 5);
        foreach ([$kept, $unpublished, $capped] as $release) {
            $this->addToCart($release, 3);
        }
        $connection = $this->entityManager()->getConnection();
        $connection->executeStatement('UPDATE article SET published = false WHERE id = ?', [$unpublished->getId()]);
        $connection->executeStatement('UPDATE article SET stock = 1 WHERE id = ?', [$capped->getId()]);

        $crawler = $this->client->request('GET', '/cart');

        self::assertCount(0, $crawler->filter(\sprintf('form[action="/cart/remove/%d"]', $unpublished->getId())));
        self::assertSame('3', $crawler->filter(\sprintf('form[action="/cart/remove/%d"]', $kept->getId()))->closest('tr')?->filter('input.form-control')->attr('value'));
        self::assertSame('1', $crawler->filter(\sprintf('form[action="/cart/remove/%d"]', $capped->getId()))->closest('tr')?->filter('input.form-control')->attr('value'));
    }

    private function addToCart(Article $article, int $quantity = 1): void
    {
        $uri = \sprintf('/catalog/%s/%s', $article->getSupportType()?->value, $article->getSlug());
        $this->client->submit($this->client->request('GET', $uri)->filter('form[name="add_to_cart_with_quantity"]')->form([
            'add_to_cart_with_quantity[quantity]' => (string) $quantity,
        ]));
    }

    private function queriesOf(string $uri): int
    {
        $this->client->enableProfiler();
        $this->client->request('GET', $uri);
        $collector = $this->client->getProfile()->getCollector('db');
        self::assertInstanceOf(DoctrineDataCollector::class, $collector);

        return $collector->getQueryCount();
    }
}
