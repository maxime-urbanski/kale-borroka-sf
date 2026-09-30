<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Book;
use App\Entity\Merch;
use App\Entity\Release;
use App\Entity\Song;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * How the back-office forms are organised: which screens use tabs, and what goes in them.
 */
class AdminLayoutTest extends WebTestCase
{
    use OrderTestTrait;

    private ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->beginIsolation();
        $this->client->loginUser($this->user('maxiloud@gmail.com'));
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testArticlesAreEditedInFicheVenteVisuelsTabs(): void
    {
        foreach (['release' => Release::class, 'book' => Book::class] as $crud => $class) {
            $id = $this->entityManager()->getRepository($class)->findOneBy([])?->getId();
            self::assertSame(['Fiche', 'Vente', 'Visuels'], $this->tabs(\sprintf('/admin/%s/%d/edit', $crud, $id)), $crud);
            self::assertSame(['Fiche', 'Vente', 'Visuels'], $this->tabs(\sprintf('/admin/%s/new', $crud)), $crud.' new');
        }
    }

    public function testMerchTabsCountItsSizes(): void
    {
        $merch = $this->entityManager()->getRepository(Merch::class)->findOneBy(['name' => 'T-shirt Brixton Cats']);

        self::assertSame(['Produit', 'Tailles & stock 4', 'Visuels'], $this->tabs(\sprintf('/admin/merch/%d/edit', $merch?->getId())));
    }

    /**
     * Entries of a collection (pressings in an album) get compact rows, never nested tabs.
     */
    public function testAlbumTabsCountTheirContentAndDoNotNestTabs(): void
    {
        $album = $this->entityManager()->getRepository(Album::class)->findOneBy(['name' => 'Quartier Maudit']);
        self::assertInstanceOf(Album::class, $album);

        $crawler = $this->client->request('GET', \sprintf('/admin/album/%d/edit', $album->getId()));

        self::assertSame(
            ['Infos', 'Tracklist '.$album->getTracklists()->count(), 'Pressages '.$album->getReleases()->count(), 'Visuels'],
            $this->tabLabels($crawler),
        );
        self::assertCount(1, $crawler->filter('.nav-tabs'), 'no tabs inside the pressing entries');
    }

    public function testOrderAndUserDetailPagesUseTabs(): void
    {
        [$release] = $this->releasesWithStock(3);
        $order = $this->placeOrder($this->user('test@test.fr'), [$release->getId() => 1]);

        self::assertSame(['Commande', 'Articles 1', 'Livraison'], $this->tabs(\sprintf('/admin/order/%d', $order->getId())));
        self::assertSame(['Compte', 'Commandes 1', 'Adresses'], $this->tabs(\sprintf('/admin/user/%d', $this->user('test@test.fr')->getId())));
        // Editing an account is short: fieldsets, no tabs.
        self::assertSame([], $this->tabs(\sprintf('/admin/user/%d/edit', $this->user('test@test.fr')->getId())));
    }

    public function testTrackDurationsAreTypedAsMinutesAndSeconds(): void
    {
        $album = $this->entityManager()->getRepository(Album::class)->findOneBy(['name' => 'Quartier Maudit']);
        $form = $this->client->request('GET', \sprintf('/admin/album/%d/edit', $album?->getId()))->filter('form[name="Album"]')->form();
        $values = $form->getPhpValues();
        $values['Album']['tracklists'] = [['track' => '1', 'name' => 'Intro', 'duration' => '3:45']];

        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseRedirects();

        $song = $this->entityManager()->getRepository(Song::class)->findOneBy(['name' => 'Intro'], ['id' => 'DESC']);
        self::assertSame(225, $song?->getDuration());

        $values['Album']['tracklists'] = [['track' => '1', 'name' => 'Intro', 'duration' => 'trois minutes']];
        $crawler = $this->client->request('POST', $form->getUri(), $values);
        self::assertStringContainsString('utilisez le format 3:45', $crawler->text());
    }

    public function testArtistLinksAreAListOfUrls(): void
    {
        $artist = $this->entityManager()->getRepository(Artist::class)->findOneBy(['name' => 'Brixton Cats']);
        $form = $this->client->request('GET', \sprintf('/admin/artist/%d/edit', $artist?->getId()))->filter('form[name="Artist"]')->form();
        $values = $form->getPhpValues();
        $values['Artist']['links'] = ['https://brixtoncats.bandcamp.com', '', 'https://instagram.com/brixtoncats'];

        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseRedirects();

        $artist = $this->entityManager()->find(Artist::class, $artist?->getId());
        self::assertSame(['https://brixtoncats.bandcamp.com', 'https://instagram.com/brixtoncats'], $artist?->getLinks());
    }

    /**
     * @return list<string>
     */
    private function tabs(string $uri): array
    {
        $crawler = $this->client->request('GET', $uri);
        self::assertResponseIsSuccessful($uri);

        return $this->tabLabels($crawler);
    }

    /**
     * @return list<string>
     */
    private function tabLabels(Crawler $crawler): array
    {
        // "Tracklist 10": the label, then the count badge when there is one.
        return $crawler->filter('.nav-tabs .nav-link')->each(static function (Crawler $tab): string {
            $badge = $tab->filter('.badge');
            $count = $badge->count() > 0 ? trim($badge->text()) : '';
            $label = trim((string) preg_replace('/\s+/', ' ', $tab->text()));

            return '' === $count ? $label : trim(substr($label, 0, -\strlen($count))).' '.$count;
        });
    }
}
