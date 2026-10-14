<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\FeaturedRelease;
use App\Entity\Homepage;
use App\Entity\Release;
use App\Enum\FeatureKind;
use App\Enum\HomeShelf;
use App\Enum\ItemCondition;
use App\Repository\FeaturedReleaseRepository;
use App\Service\HomepageProviderInterface;
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

    public function testTheLabelBlockSaysWhatTheFixturesSay(): void
    {
        $page = self::service(HomepageProviderInterface::class)->get()->getManifestoPage();
        self::assertNotNull($page, 'no manifesto page in the fixtures');

        $crawler = $this->client->request('GET', '/');

        $block = $crawler->filter('#le-label');
        self::assertCount(1, $crawler->filter('#dernieres-prod + #le-label'), 'after the productions');
        self::assertSame(['Pas de patron.', 'Pas de pub.', 'Pas de fachos.'], $block->filter('h2 > span')->each(static fn ($line): string => trim($line->text())));
        self::assertSame('Lire le manifeste', trim($block->filter(\sprintf('a[href="/page/%s"]', $page->getSlug()))->text()));
    }

    public function testEveryWordOfTheLabelBlockComesFromTheBackOffice(): void
    {
        $this->homepage()
            ->setManifestoKicker('Le collectif')
            ->setManifestoHeadline("Ligne un\n\n  Ligne deux  ")
            ->setManifestoHighlight('Ligne trois')
            ->setManifestoText("Premier paragraphe.\nSuite <b>gras</b>")
            ->setManifestoLinkText('Notre charte')
            ->setManifestoPage(null)
            ->setManifestoUrl('https://example.org/charte')
            ->setManifestoRule(false);
        $this->flush();

        $crawler = $this->client->request('GET', '/');

        $block = $crawler->filter('#le-label');
        self::assertStringContainsString('Le collectif', $block->text());
        self::assertSame(['Ligne un', 'Ligne deux', 'Ligne trois'], $block->filter('h2 > span')->each(static fn ($line): string => trim($line->text())));
        self::assertCount(1, $block->filter('h2 > span.kb-inset'), 'the highlighted line sits on the paper strip');
        self::assertStringContainsString('Suite <b>gras</b>', $block->filter('p')->eq(1)->text(), 'plain text, escaped');
        self::assertCount(1, $block->filter('p br'), 'line breaks kept');
        self::assertSame('Notre charte', trim($block->filter('a[href="https://example.org/charte"]')->text()));
        self::assertCount(0, $block->filter('.kb-ikurrina'));
    }

    public function testNoLinkWithoutAPublishedPageNorAnAddress(): void
    {
        $this->homepage()->getManifestoPage()?->setPublished(false);
        $this->flush();

        $this->client->request('GET', '/');
        self::assertSelectorExists('#le-label h2');
        self::assertSelectorNotExists('#le-label a');
    }

    public function testTheLabelBlockCanBeHidden(): void
    {
        $this->homepage()->setManifestoShown(false);
        $this->flush();

        $this->client->request('GET', '/');
        self::assertSelectorNotExists('#le-label');
    }

    public function testTheSectionsFollowTheBackOffice(): void
    {
        $this->homepage()
            ->setProductionsTitle('Nos sorties')
            ->setProductionsLimit(8)
            ->setProductionsLinkText('Toute la discographie')
            ->setLatestTitle('Arrivages')
            ->setLatestLimit(12)
            ->setLatestLinkText('Tout le catalogue')
            ->setLatestShelves([HomeShelf::CD, HomeShelf::ALL]);
        $this->flush();

        $crawler = $this->client->request('GET', '/');

        $productions = $crawler->filter('#dernieres-prod');
        self::assertSame('Nos sorties', trim($productions->filter('h2')->text()));
        self::assertCount(8, $productions->filter('.kb-card'));
        self::assertStringContainsString('Toute la discographie', $productions->filter('a[href="/production"]')->text());

        $latest = $crawler->filter('#derniers-articles');
        self::assertSame('Arrivages', trim($latest->filter('h2')->text()));
        self::assertSame(['Tout', 'CD'], $latest->filter('button[aria-pressed]')->each(static fn ($chip): string => trim($chip->text())), 'in the shop order');
        self::assertCount(12, $latest->filter('#rayon-tout .kb-card'));
        self::assertStringContainsString('Tout le catalogue', $latest->filter('a[href="/catalog"]')->text());
    }

    public function testASingleShelfNeedsNoChip(): void
    {
        $this->homepage()->setLatestShelves([HomeShelf::VINYL]);
        $this->flush();

        $crawler = $this->client->request('GET', '/');

        self::assertCount(0, $crawler->filter('#derniers-articles button[aria-pressed]'));
        self::assertCount(1, $crawler->filter('#rayon-vinyles:not([hidden])'));
    }

    public function testTheSectionsCanBeHidden(): void
    {
        $this->homepage()->setProductionsShown(false)->setLatestShown(false);
        $this->flush();

        $this->client->request('GET', '/');

        self::assertSelectorNotExists('#dernieres-prod');
        self::assertSelectorNotExists('#derniers-articles');
        self::assertSelectorExists('#le-label');
    }

    private function homepage(): Homepage
    {
        return self::service(HomepageProviderInterface::class)->get();
    }

    public function testTheLatestArticlesAreSortedIntoShelves(): void
    {
        $crawler = $this->client->request('GET', '/');

        $section = $crawler->filter('#derniers-articles');
        self::assertCount(1, $section);
        self::assertCount(1, $crawler->filter('#le-label + #derniers-articles'), 'after the label block');
        self::assertSame('Derniers articles', trim($section->filter('h2')->text()));

        // No second-hand copy in the fixtures: an empty shelf gets no chip.
        $chips = $section->filter('button[aria-pressed]');
        self::assertSame(['Tout', 'Vinyles', 'CD', 'K7', 'Zines'], $chips->each(static fn ($chip): string => trim($chip->text())));
        self::assertSame(['true', 'false', 'false', 'false', 'false'], $chips->each(static fn ($chip): string => (string) $chip->attr('aria-pressed')));

        // Without JavaScript, the « Tout » shelf alone shows; the chips appear with it.
        foreach ($chips->each(static fn ($chip): string => (string) $chip->attr('aria-controls')) as $index => $shelfId) {
            $panel = $section->filter('#'.$shelfId);
            self::assertCount(1, $panel);
            self::assertSame(0 !== $index, null !== $panel->attr('hidden'));
            self::assertGreaterThan(0, $panel->filter('.kb-card')->count());
            self::assertLessThanOrEqual(6, $panel->filter('.kb-card')->count());
        }

        self::assertGreaterThan(0, $section->filter('a[href="/catalog"]')->count(), '« Tous les arrivages »');
    }

    public function testAUsedCopyOpensTheSecondHandShelf(): void
    {
        $copy = self::service(EntityManagerInterface::class)->getRepository(Release::class)->findOneBy(['published' => true]);
        $copy?->setItemCondition(ItemCondition::USED);
        $this->flush();

        $this->client->request('GET', '/');

        self::assertSelectorTextContains('#derniers-articles button[aria-controls="rayon-occasion"]', 'Occasion');
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
