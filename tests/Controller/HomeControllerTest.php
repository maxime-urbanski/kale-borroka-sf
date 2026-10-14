<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\FeaturedRelease;
use App\Enum\FeatureKind;
use App\Repository\FeaturedReleaseRepository;
use App\Tests\DatabaseIsolationTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    use DatabaseIsolationTrait;

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

    public function testHomeController(): void
    {
        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
    }

    public function testThePreorderIsFeaturedAtTheTop(): void
    {
        $album = $this->feature(stock: 10)->getRelease()?->getAlbum();
        self::assertNotNull($album);
        $title = (string) $album->getName();
        $artist = (string) $album->getArtist()?->getName();
        $this->flush();

        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.kb-feature .kb-stamp', 'Prévente');
        self::assertSelectorTextContains('.kb-feature h1', $title);
        self::assertSelectorTextContains('.kb-feature h1', $artist);
        self::assertSelectorTextContains('.kb-feature', 'Précommander');
        self::assertCount(1, $crawler->filter('.kb-feature a[href="https://kaleborroka.bandcamp.com"]'));
    }

    public function testThePreorderButtonPutsTheReleaseInTheCart(): void
    {
        $name = (string) $this->feature(stock: 10)->getRelease()?->getName();
        $this->flush();

        $crawler = $this->client->request('GET', '/');
        $this->client->submit($crawler->filter('.kb-feature form')->form());
        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Article ajouté au panier.');

        $this->client->request('GET', '/cart');
        self::assertSelectorTextContains('main', $name);
    }

    public function testAFavouriteIsSoldAsAnyRecord(): void
    {
        $this->feature(stock: 10)->setKind(FeatureKind::FAVORITE);
        $this->flush();

        $this->client->request('GET', '/');

        self::assertSelectorTextContains('.kb-feature .kb-stamp', 'Coup de cœur');
        self::assertSelectorTextContains('.kb-feature', 'Ajouter au panier');
        self::assertSelectorTextNotContains('.kb-feature', 'Précommander');
    }

    public function testASoldOutFeatureHasNoBuyButton(): void
    {
        $this->feature(stock: 0);
        $this->flush();

        $crawler = $this->client->request('GET', '/');

        self::assertSelectorTextContains('.kb-feature', 'Épuisé');
        self::assertCount(0, $crawler->filter('.kb-feature form'));
    }

    public function testNoFeatureWhenNoneIsOnline(): void
    {
        foreach (self::service(FeaturedReleaseRepository::class)->findAll() as $feature) {
            $feature->setPublished(false);
        }
        $this->flush();

        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('.kb-feature');
    }

    public function testTheLatestProductionsAreListedUnderTheFeature(): void
    {
        $crawler = $this->client->request('GET', '/');

        $section = $crawler->filter('#dernieres-prod');
        self::assertCount(1, $section);
        self::assertSame('Dernières prod', trim($section->filter('h2')->text()));
        self::assertCount(4, $section->filter('.kb-card'));
        self::assertStringContainsString('KBR#010', $section->filter('.kb-card')->first()->text());
        self::assertCount(1, $section->filter('a[href="/production"]'), 'link to the label catalogue');

        // Under the feature, before the latest articles.
        $order = $crawler->filter('.kb-feature, #dernieres-prod, #derniers-articles')->each(static fn ($node): string => (string) ($node->attr('id') ?? 'feature'));
        self::assertSame(['feature', 'dernieres-prod'], \array_slice($order, 0, 2));
    }

    /**
     * The feature of the fixtures, with the stock of its release set: flush() saves it.
     */
    private function feature(int $stock): FeaturedRelease
    {
        $feature = self::service(FeaturedReleaseRepository::class)->findCurrent(new \DateTimeImmutable());
        self::assertNotNull($feature, 'no featured release online in the fixtures');
        $feature->getRelease()?->setStock($stock);

        return $feature;
    }

    private function flush(): void
    {
        $entityManager = self::service(EntityManagerInterface::class);
        $entityManager->flush();
        $entityManager->clear();
    }
}
