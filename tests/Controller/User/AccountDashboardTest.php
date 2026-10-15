<?php

declare(strict_types=1);

namespace App\Tests\Controller\User;

use App\Entity\Release;
use App\Entity\User;
use App\Entity\WishlistItem;
use App\Repository\WishlistItemRepository;
use App\Repository\WishlistRepository;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Mon compte, tableau de bord (maquette 07): latest orders with their status, collection
 * and wantlist as crates, the wantlist articles back in stock, and the account menu.
 */
class AccountDashboardTest extends WebTestCase
{
    use OrderTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->beginIsolation();
        $this->client->loginUser($this->customer());
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testItGreetsTheCustomer(): void
    {
        $crawler = $this->dashboard();

        self::assertSelectorTextSame('h1', 'Kaixo, Test');
        self::assertSelectorTextContains('.kb-account__since', 'Membre depuis '.$this->customer()->getCreatedAt()?->format('Y'));
        self::assertSame('page', $crawler->filter('nav[aria-label="Mon compte"] a')->first()->attr('aria-current'));
    }

    public function testTheMenuCountsTheCustomersOwnThings(): void
    {
        $crawler = $this->dashboard();

        // Fixtures: two addresses, two collection items, two wantlist items, no order.
        self::assertSame('0', $this->menuCount($crawler, 'Commandes'));
        self::assertSame('2', $this->menuCount($crawler, 'Collection'));
        self::assertSame('2', $this->menuCount($crawler, 'Wantlist'));
        self::assertSame('2', $this->menuCount($crawler, 'Adresses'));
        self::assertSelectorExists('nav[aria-label="Mon compte"] form[action="/logout"]');
    }

    public function testTheWantlistAlertNamesTheArticlesBackInStock(): void
    {
        [$inStock, $soldOut] = $this->wantlist();
        $this->setStock($inStock, 3);
        $this->setStock($soldOut, 0);

        $crawler = $this->dashboard();

        $alert = $crawler->filter('.kb-account-alert');
        self::assertCount(1, $alert);
        self::assertStringContainsString('1 article de ta wantlist est en stock', $alert->text());
        self::assertStringContainsString(self::titleOf($inStock), $alert->text());
        self::assertStringNotContainsString(self::titleOf($soldOut), $alert->text());
        self::assertSelectorTextContains('.kb-crate--wantlist .kb-crate__count', '2 articles · 1 dispo');
    }

    public function testTheAlertEscapesTheNamesOnce(): void
    {
        [$inStock] = $this->wantlist();
        $this->setStock($inStock, 1);
        $article = $inStock->getArticle();
        if ($article instanceof Release) {
            $article->getAlbum()?->setName('Kale & Borroka');
        } else {
            $article?->setName('Kale & Borroka');
        }
        $this->entityManager()->flush();

        $this->dashboard();

        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Kale &amp; Borroka', $html);
        self::assertStringNotContainsString('&amp;amp;', $html);
    }

    public function testAnUnpublishedArticleIsNotBackInStock(): void
    {
        foreach ($this->wantlist() as $item) {
            $this->setStock($item, 5);
            $item->getArticle()?->setPublished(false);
        }
        $this->entityManager()->flush();

        $this->dashboard();

        self::assertSelectorNotExists('.kb-account-alert');
    }

    public function testTheLatestOrdersShowTheirStatusAndLinkToTheirDetail(): void
    {
        [$release] = $this->releasesWithStock(10);
        $orders = [];
        foreach ([1, 2, 1, 3, 1] as $quantity) {
            $orders[] = $this->placeOrder($this->customer(), [self::idOf($release) => $quantity]);
        }

        $crawler = $this->dashboard();

        $rows = $crawler->filter('.kb-orders tbody tr');
        self::assertCount(4, $rows, 'the four latest orders');
        $first = $rows->first();
        self::assertStringContainsString((string) $orders[4]->getReference(), $first->text());
        self::assertStringContainsString('1 article', $first->text());
        self::assertStringContainsString('En attente de paiement', $first->filter('.kb-badge')->text());
        self::assertSame('/order/'.$orders[4]->getReference().'/overview', $first->filter('a')->attr('href'));
        self::assertStringContainsString('3 articles', $rows->eq(1)->text());
        self::assertSame('5', $this->menuCount($crawler, 'Commandes'));
    }

    public function testACustomerWithoutOrdersIsToldSo(): void
    {
        $this->dashboard();

        self::assertSelectorNotExists('.kb-orders');
        self::assertSelectorTextContains('.kb-account-orders', 'Pas encore de commande');
    }

    public function testTheCratesAndPanelsLinkToTheAccountPages(): void
    {
        $crawler = $this->dashboard();

        self::assertSelectorTextContains('.kb-crate--collection .kb-crate__count', '2 articles');
        self::assertCount(2, $crawler->filter('.kb-crate--collection a.kb-crate__cover'));
        self::assertCount(1, $crawler->filter('.kb-crate--collection a[href="/mon-compte/collection"]'));
        self::assertCount(1, $crawler->filter('.kb-crate--wantlist a[href="/mon-compte/wishlist"]'));
        self::assertSelectorTextContains('.kb-account-panel--address', '5 rue des Tonneliers, 20003 Donostia');
        self::assertSelectorTextContains('.kb-account-panel--address', '+ 1 autre adresse');
        self::assertSelectorTextContains('.kb-account-panel--informations', 'test@test.fr');
    }

    public function testAnUnpublishedArticleKeepsItsCoverWithoutALink(): void
    {
        [$unpublished] = $this->wantlist();
        $unpublished->getArticle()?->setPublished(false);
        $this->entityManager()->flush();

        $crawler = $this->dashboard();

        self::assertCount(2, $crawler->filter('.kb-crate--wantlist .kb-crate__cover'));
        self::assertCount(1, $crawler->filter('.kb-crate--wantlist a.kb-crate__cover'));
    }

    public function testWithoutADefaultAddressTheAddressBookIsCounted(): void
    {
        foreach ($this->customer()->getAddresses() as $address) {
            $address->setIsMainAddress(false);
        }
        $this->entityManager()->flush();

        $this->dashboard();

        self::assertSelectorTextContains('.kb-account-panel--address', 'Pas encore d’adresse par défaut.');
        self::assertSelectorTextContains('.kb-account-panel--address', '2 adresses dans ton carnet');
        self::assertSelectorTextNotContains('.kb-account-panel--address', 'autre');
    }

    private function customer(): User
    {
        return $this->user('test@test.fr');
    }

    private function dashboard(): Crawler
    {
        $crawler = $this->client->request('GET', '/mon-compte');
        self::assertResponseIsSuccessful();

        return $crawler;
    }

    private function menuCount(Crawler $crawler, string $label): string
    {
        $link = $crawler->filter('nav[aria-label="Mon compte"] a')->reduce(
            static fn (Crawler $a): bool => str_starts_with(trim($a->filter('.kb-account-nav__label')->text()), $label),
        );
        self::assertCount(1, $link, $label);

        return trim($link->filter('.kb-account-nav__count')->text());
    }

    /**
     * @return list<WishlistItem>
     */
    private function wantlist(): array
    {
        $wishlist = self::service(WishlistRepository::class)->findOneBy(['user' => $this->customer()]);
        $items = self::service(WishlistItemRepository::class)->findBy(['wishlist' => $wishlist]);
        self::assertCount(2, $items);

        return $items;
    }

    /**
     * A record is named after its album, other articles after themselves.
     */
    private static function titleOf(WishlistItem $item): string
    {
        $article = $item->getArticle();

        return (string) ($article instanceof Release ? $article->getAlbum()?->getName() : $article?->getName());
    }

    private function setStock(WishlistItem $item, int $stock): void
    {
        $item->getArticle()?->setStock($stock)->setPublished(true);
        $this->entityManager()->flush();
    }
}
