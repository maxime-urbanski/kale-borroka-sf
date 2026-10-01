<?php

declare(strict_types=1);

namespace App\Tests\Controller\Catalog;

use App\Entity\Artist;
use App\Entity\Release;
use App\Entity\Song;
use App\Repository\ReleaseRepository;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
        self::assertSelectorTextContains('form button.btn-success', 'Ajouter au panier');
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
        self::assertSame('1 - Pour les braves 2:34', preg_replace('/\s+/', ' ', trim($tracks->first()->text())));
        self::assertStringNotContainsString('Brixton Cats', $tracks->text(), 'The album artist is not repeated on each track.');
    }

    public function testATrackByAnotherBandIsCreditedToIt(): void
    {
        $release = $this->quartierMaudit();
        $guest = $this->entityManager()->getRepository(Artist::class)->findOneBy(['name' => 'Moscow Death Brigade']);
        self::assertNotNull($guest);
        $song = $release->getAlbum()?->getTracklists()->get(1);
        self::assertNotNull($song);
        $song->getArtist()->clear();
        $song->addArtist($guest);
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', $this->uriOf($release));

        self::assertStringContainsString('(Moscow Death Brigade)', $crawler->filter('section ol li')->eq(1)->text());
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
