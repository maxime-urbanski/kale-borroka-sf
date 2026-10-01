<?php

declare(strict_types=1);

namespace App\Tests\Controller\Catalog;

use App\Entity\Artist;
use App\Entity\Release;
use App\Entity\Song;
use App\Enum\ReleaseFormat;
use App\Repository\ReleaseRepository;
use App\Tests\Order\OrderTestTrait;
use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * What the article page shows of a release: whether it can be bought, and its tracklist.
 */
class ArticlePageContentTest extends WebTestCase
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

    public function testAReleaseInStockCanBeAddedToTheCart(): void
    {
        $crawler = $this->client->request('GET', $this->uriOf($this->quartierMaudit()));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('form button.btn-primary', 'Ajouter au panier');
        self::assertSelectorTextNotContains('body', 'Épuisé');
        self::assertCount(1, $crawler->filter('input[name$="[quantity]"]'));
    }

    public function testASoldOutReleaseShowsNoAddToCartButton(): void
    {
        $release = $this->quartierMaudit()->setStock(0);
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', $this->uriOf($release));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Épuisé');
        self::assertCount(0, $crawler->filter('input[name$="[quantity]"]'));
        self::assertStringNotContainsString('Ajouter au panier', $crawler->filter('body')->text());
    }

    public function testTheTracklistShowsEachTrackWithItsDuration(): void
    {
        $release = $this->quartierMaudit();
        $first = $release->getAlbum()?->getTracklists()->first();
        self::assertInstanceOf(Song::class, $first);
        $first->setDuration(154);
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', $this->uriOf($release));

        $tracks = $crawler->filter('section ol li');
        self::assertCount(10, $tracks);
        self::assertSame('A1 - Pour les braves 2:34', preg_replace('/\s+/', ' ', trim($tracks->first()->text())));
        self::assertStringNotContainsString('Brixton Cats', $crawler->filter('section ol')->text(), 'The album artist is not repeated on each track.');
    }

    public function testATrackByOtherBandsIsCreditedToEachOfThem(): void
    {
        $release = $this->quartierMaudit();
        $song = $release->getAlbum()?->getTracklists()->get(1);
        self::assertNotNull($song);
        // Credited to the album artist and two guests: only the guests are named.
        foreach (['Moscow Death Brigade', 'Krav Boca'] as $name) {
            $guest = $this->entityManager()->getRepository(Artist::class)->findOneBy(['name' => $name]);
            self::assertNotNull($guest);
            $song->addArtist($guest);
        }
        $song->setDuration(3725);
        $this->entityManager()->flush();
        // As a real request would: the page loads the tracklist afresh, not the collections above.
        $this->entityManager()->clear();

        $crawler = $this->client->request('GET', $this->uriOf($release));

        $track = preg_replace('/\s+/', ' ', trim($crawler->filter('section ol li')->eq(1)->text()));
        self::assertSame('A2 - Religion (Krav Boca, Moscow Death Brigade) 1:02:05', $track);
    }

    public function testAnLpTracklistIsSplitIntoItsSides(): void
    {
        $crawler = $this->client->request('GET', $this->uriOf($this->quartierMaudit()));

        self::assertSame(['Face A', 'Face B'], $crawler->filter('section h3')->each(static fn ($title): string => trim($title->text())));
        self::assertCount(5, $crawler->filter('section ol')->first()->filter('li'));
        self::assertStringStartsWith('B1 - Choisir sa vie', trim($crawler->filter('section ol')->last()->filter('li')->first()->text()));
    }

    /**
     * The same album pressed on CD: one list, numbered, whatever the vinyl sides.
     */
    public function testACdTracklistPlaysStraightThrough(): void
    {
        $album = $this->quartierMaudit()->getAlbum();
        self::assertNotNull($album);
        $cd = (new Release())->setName('Brixton Cats - Quartier Maudit (CD)')->setSku('TEST-QM-CD')->setFormat(ReleaseFormat::CD)
            ->setPrice(1000)->setStock(3)->setPublished(true);
        $album->addRelease($cd);
        $this->entityManager()->persist($cd);
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', $this->uriOf($cd));

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('section h3'), 'no side on a CD');
        $tracks = $crawler->filter('section ol li');
        self::assertCount(10, $tracks);
        self::assertStringStartsWith('6 - Choisir sa vie', trim($tracks->eq(5)->text()));
    }

    /**
     * A cache miss loads the songs and their artists at once, not one query per track.
     */
    public function testTheTracklistTakesASingleQuery(): void
    {
        $this->client->enableProfiler();
        $this->client->request('GET', $this->uriOf($this->quartierMaudit()));

        $profile = $this->client->getProfile();
        self::assertInstanceOf(Profile::class, $profile);
        $collector = $profile->getCollector('db');
        self::assertInstanceOf(DoctrineDataCollector::class, $collector);
        $songQueries = array_filter(
            array_merge(...array_values($collector->getQueries())),
            static fn (array $query): bool => str_contains((string) $query['sql'], 'song'),
        );

        self::assertCount(1, $songQueries);
    }

    public function testAddingMoreThanTheStockSaysSo(): void
    {
        $release = $this->quartierMaudit()->setStock(1);
        $this->entityManager()->flush();

        $this->client->request('GET', $this->uriOf($release));
        $this->client->submitForm('Ajouter au panier');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Article ajouté au panier.');

        $this->client->submitForm('Ajouter au panier');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Tout le stock disponible est déjà dans votre panier.');
        self::assertSelectorTextNotContains('body', 'Article ajouté au panier.');
    }

    private function quartierMaudit(): Release
    {
        $release = self::service(ReleaseRepository::class)->findOneBy(['sku' => 'KBR-QM-LP-BLK']);
        self::assertNotNull($release, 'the fixtures should provide Brixton Cats - Quartier Maudit');

        return $release;
    }

    private function uriOf(Release $release): string
    {
        return \sprintf('/catalog/%s/%s', $release->getSupportType()?->value, $release->getSlug());
    }
}
