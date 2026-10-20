<?php

declare(strict_types=1);

namespace App\Tests\Controller\User;

use App\Catalog\RecordNames;
use App\Entity\UserCollectionItems;
use App\Entity\WishlistItem;
use App\Repository\UserCollectionItemsRepository;
use App\Repository\UserCollectionRepository;
use App\Repository\WishlistItemRepository;
use App\Repository\WishlistRepository;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Wantlist (maquette 11) and collection (maquette 12): the two bins of the wantlist, and the
 * collection's filters, search and views.
 */
class WantlistCollectionTest extends WebTestCase
{
    use OrderTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->beginIsolation();
        $this->client->loginUser($this->user('test@test.fr'));
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testTheWantlistSplitsWhatCanBeBoughtFromWhatIsOnWatch(): void
    {
        [$inStock, $soldOut] = $this->wantlist();
        $inStock->getArticle()?->setStock(2)->setPublished(true);
        $soldOut->getArticle()?->setStock(0)->setPublished(true);
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/mon-compte/wishlist');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Wantlist');
        self::assertSelectorTextContains('.kb-account__since', '2 disques · 1 dispo');
        self::assertCount(1, $crawler->filter('.kb-want'));
        self::assertCount(1, $crawler->filter('.kb-watch__item'));
        self::assertSelectorTextContains('.kb-want', 'Plus que 2');
        self::assertSelectorTextContains('.kb-watch__item', 'Épuisé');
        // Every button says what it acts on.
        $labels = $crawler->filter('.kb-want button, .kb-watch__item button')->each(static fn (Crawler $button): string => trim((string) $button->attr('aria-label')));
        self::assertCount(4, $labels, 'remove and cart, then alert switch and remove');
        self::assertNotContains('', $labels);
        self::assertCount(1, $crawler->filter(\sprintf('.kb-want form[action="/cart/add/%d"]', $inStock->getArticle()?->getId())));
        self::assertCount(2, $crawler->filter('form[action^="/wishlist/remove/"]'));
    }

    public function testAnUnpublishedArticleIsOnWatchWithoutALink(): void
    {
        [$first, $second] = $this->wantlist();
        $first->getArticle()?->setStock(5)->setPublished(false);
        $second->getArticle()?->setStock(5)->setPublished(false);
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/mon-compte/wishlist');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('.kb-want');
        self::assertCount(2, $crawler->filter('.kb-watch__item'));
        self::assertSelectorNotExists('.kb-watch__item a');
        self::assertSelectorTextContains('.kb-watch__item', 'Pas en ligne');
    }

    public function testAnEmptyWantlistSaysSo(): void
    {
        foreach ($this->wantlist() as $item) {
            self::service(WishlistItemRepository::class)->remove($item, true);
        }

        $this->client->request('GET', '/mon-compte/wishlist');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.kb-account__empty', 'Rien dans ta wantlist');
    }

    public function testTheCollectionShowsCoversAndCountsBySupport(): void
    {
        $crawler = $this->client->request('GET', '/mon-compte/collection');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Ma collection');
        self::assertCount(2, $crawler->filter('.kb-shelf__item'));
        self::assertSame('true', $crawler->filter('nav[aria-label="Filtrer par support"] a')->first()->attr('aria-current'));
        self::assertSame('true', $crawler->filter('nav[aria-label="Affichage"] a')->first()->attr('aria-current'));
        self::assertStringContainsString('Tout 2', preg_replace('/\s+/', ' ', $crawler->filter('nav[aria-label="Filtrer par support"] a')->first()->text()) ?? '');
        self::assertSelectorExists('form[role="search"] label[for="collection-q"]');
    }

    public function testTheListViewIsATableWithHeaders(): void
    {
        $crawler = $this->client->request('GET', '/mon-compte/collection?vue=liste');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $crawler->filter('table.kb-records tbody tr'));
        self::assertSelectorExists('table.kb-records caption');
        self::assertSame('true', $crawler->filter('nav[aria-label="Affichage"] a')->eq(1)->attr('aria-current'));
    }

    public function testASupportFilterKeepsOnlyItsRecords(): void
    {
        $crawler = $this->client->request('GET', '/mon-compte/collection');
        $supports = $crawler->filter('nav[aria-label="Filtrer par support"] a')->each(static fn (Crawler $a): ?string => $a->attr('href'));
        self::assertGreaterThan(1, \count($supports), 'a link per support held, after « Tout »');

        $filtered = $this->client->request('GET', (string) $supports[1]);

        self::assertResponseIsSuccessful();
        self::assertSame('true', $filtered->filter('nav[aria-label="Filtrer par support"] a')->eq(1)->attr('aria-current'));
        self::assertLessThanOrEqual(2, $filtered->filter('.kb-shelf__item')->count());
    }

    public function testASearchWithNoMatchOffersToShowEverything(): void
    {
        $this->client->request('GET', '/mon-compte/collection?q=zzzzzzzz');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('.kb-shelf');
        self::assertSelectorTextContains('.kb-account__empty', 'Aucun disque ne correspond');
        self::assertSelectorExists('input[name="q"][value="zzzzzzzz"]');
    }

    public function testASearchMatchesTheArtistOrTheTitle(): void
    {
        $item = $this->collection()[0];
        $names = $this->crawlerNames($item);

        $crawler = $this->client->request('GET', '/mon-compte/collection?q='.rawurlencode(strtoupper($names)));

        self::assertResponseIsSuccessful();
        self::assertGreaterThanOrEqual(1, $crawler->filter('.kb-shelf__item')->count());
    }

    public function testAnUnknownSupportIsIgnored(): void
    {
        $crawler = $this->client->request('GET', '/mon-compte/collection?support=vhs&vue=nope');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $crawler->filter('.kb-shelf__item'));
    }

    public function testACollectionRecordCanBeRemovedWithAnExplicitLabel(): void
    {
        $crawler = $this->client->request('GET', '/mon-compte/collection');

        $buttons = $crawler->filter('.kb-shelf__remove');
        self::assertCount(2, $buttons);
        self::assertStringStartsWith('Retirer ', (string) $buttons->first()->attr('aria-label'));

        $this->client->submit($buttons->first()->ancestors()->filter('form')->first()->form());
        self::assertResponseRedirects();
        $this->client->request('GET', '/mon-compte/collection');
        self::assertCount(1, $this->client->getCrawler()->filter('.kb-shelf__item'));
    }

    public function testTheCollectionIsPaginated(): void
    {
        $this->client->request('GET', '/mon-compte/collection/page-2');

        self::assertResponseIsSuccessful();
    }

    /**
     * @return list<WishlistItem>
     */
    private function wantlist(): array
    {
        $wishlist = self::service(WishlistRepository::class)->findOneBy(['user' => $this->user('test@test.fr')]);
        $items = self::service(WishlistItemRepository::class)->findBy(['wishlist' => $wishlist]);
        self::assertCount(2, $items);

        return $items;
    }

    /**
     * @return list<UserCollectionItems>
     */
    private function collection(): array
    {
        $collection = self::service(UserCollectionRepository::class)->findOneBy(['user' => $this->user('test@test.fr')]);

        return self::service(UserCollectionItemsRepository::class)->findBy(['collection' => $collection]);
    }

    private function crawlerNames(UserCollectionItems $item): string
    {
        return RecordNames::of($item->getArticle() ?? throw new \LogicException('no article'))['title'];
    }
}
