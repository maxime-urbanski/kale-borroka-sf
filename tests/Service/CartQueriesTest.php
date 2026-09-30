<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Article;
use App\Tests\Order\OrderTestTrait;
use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * The navbar shows the cart on every page, the cart and delivery pages list it: none of
 * them may run more queries as the cart grows.
 */
class CartQueriesTest extends WebTestCase
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
     * Every page shows the cart count: one scalar query whatever the number of lines, none
     * for an empty cart.
     */
    public function testTheNavbarCountCostsOneQuery(): void
    {
        // A page without articles of its own: only the navbar could read the cart.
        $releases = $this->releasesWithStock(5, 5, 5, 5);
        $emptyCart = $this->queriesOf('/login');

        $this->addToCart($releases[0]);
        $oneLine = $this->queriesOf('/login');

        foreach (\array_slice($releases, 1) as $i => $release) {
            $this->addToCart($release, $i + 2);
        }
        $fourLines = $this->queriesOf('/login');

        self::assertSame($emptyCart + 1, $oneLine);
        self::assertSame($oneLine, $fourLines);
        self::assertSelectorTextSame('header .badge', '9+', '1 + 2 + 3 + 4 items');
    }

    /**
     * An article unpublished or sold since it was added no longer counts, on any page.
     */
    public function testTheNavbarCountFollowsTheShop(): void
    {
        [$kept, $unpublished, $soldOut, $capped] = $this->releasesWithStock(5, 5, 5, 5);
        foreach ([$kept, $unpublished, $soldOut, $capped] as $release) {
            $this->addToCart($release, 2);
        }
        $connection = $this->entityManager()->getConnection();
        $connection->executeStatement('UPDATE article SET published = false WHERE id = ?', [$unpublished->getId()]);
        $connection->executeStatement('UPDATE article SET stock = 0 WHERE id = ?', [$soldOut->getId()]);
        $connection->executeStatement('UPDATE article SET stock = 1 WHERE id = ?', [$capped->getId()]);

        $this->client->request('GET', '/login');

        self::assertSelectorTextSame('header .badge', '3', '2 kept + 1 capped');
    }

    public function testTheNavbarCountsItems(): void
    {
        [$first, $second] = $this->releasesWithStock(5, 5);
        $this->addToCart($first, 2);
        $this->addToCart($second, 3);

        $this->client->request('GET', '/');

        self::assertSelectorTextSame('header .badge', '5');
    }

    /**
     * Articles, their images and their albums are loaded in batches, not line by line.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('cartPages')]
    public function testCartPagesDoNotGrowWithTheLines(string $page): void
    {
        $this->client->loginUser($this->user('test@test.fr'));
        $releases = $this->releasesWithStock(5, 5, 5, 5);

        $this->addToCart($releases[0]);
        $oneLine = $this->queriesOf($page);

        foreach (\array_slice($releases, 1) as $release) {
            $this->addToCart($release);
        }
        $fourLines = $this->queriesOf($page);

        self::assertSame($oneLine, $fourLines);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function cartPages(): iterable
    {
        yield 'cart' => ['/cart'];
        yield 'delivery' => ['/order/delivery'];
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
        // A first, unmeasured request: the collector also counts the queries run since the
        // previous request, such as this test's own setup.
        $this->client->request('GET', $uri);
        $this->client->enableProfiler();
        $this->client->request('GET', $uri);
        $profile = $this->client->getProfile();
        self::assertInstanceOf(Profile::class, $profile);
        $collector = $profile->getCollector('db');
        self::assertInstanceOf(DoctrineDataCollector::class, $collector);

        return $collector->getQueryCount();
    }
}
