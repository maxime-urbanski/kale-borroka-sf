<?php

declare(strict_types=1);

namespace App\Tests\Controller\Catalog;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Book;
use App\Entity\Label;
use App\Entity\MerchVariant;
use App\Entity\Release;
use App\Entity\Style;
use App\Enum\AlbumReleaseType;
use App\Enum\ItemCondition;
use App\Enum\ReleaseFormat;
use App\Tests\DatabaseIsolationTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * The catalogue page: every section at /catalog, one at /catalog/{support}, filters in the
 * query string. Most cases scope the results with a label of their own, so the counts do
 * not depend on the random fixtures.
 */
class CatalogPageTest extends WebTestCase
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

    public function testTheCatalogListsEveryPublishedRecordAndFanzineButNoMerch(): void
    {
        $em = $this->em();
        $expected = (int) $em->createQuery('SELECT COUNT(r.id) FROM '.Release::class.' r WHERE r.published = true AND r.format IS NOT NULL')->getSingleScalarResult()
            + (int) $em->createQuery('SELECT COUNT(b.id) FROM '.Book::class.' b WHERE b.published = true')->getSingleScalarResult();
        self::assertGreaterThan(0, (int) $em->createQuery('SELECT COUNT(m.id) FROM '.MerchVariant::class.' m WHERE m.published = true')->getSingleScalarResult());

        $crawler = $this->client->request('GET', '/catalog');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('h1', 'Catalogue');
        self::assertSelectorTextSame('.kb-catalog__count', \sprintf('%d références', $expected));
        self::assertSelectorTextSame('.kb-tabs [aria-current="page"]', 'Tout');
        self::assertSame(['Tout', 'LP', 'EP / 7"', 'CD', 'K7', 'Fanzines'], $crawler->filter('.kb-tabs a')->each(static fn (Crawler $a): string => trim($a->text())));
        self::assertCount(12, $crawler->filter('.kb-catalog__grid article'));
        // The section group is only offered on « Tout ».
        self::assertCount(1, $crawler->filter('fieldset[data-group="supports"]'));
    }

    public function testASectionPageHasItsTitleAndNoSectionGroup(): void
    {
        $crawler = $this->client->request('GET', '/catalog/tape');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('h1', 'K7');
        self::assertSelectorTextSame('.kb-tabs [aria-current="page"]', 'K7');
        self::assertCount(0, $crawler->filter('fieldset[data-group="supports"]'));
        self::assertGreaterThan(0, $crawler->filter('.kb-catalog__grid article')->count());
    }

    public function testAnUnknownSectionIsNotFound(): void
    {
        $this->client->request('GET', '/catalog/cdlp');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPagesAreInThePath(): void
    {
        $label = $this->label('Label Pagination');
        for ($i = 1; $i <= 13; ++$i) {
            $this->release('Pagination '.$i, $label, format: ReleaseFormat::VINYL_12);
        }
        $this->em()->flush();

        $this->client->request('GET', '/catalog/page-2');
        self::assertResponseIsSuccessful();

        $crawler = $this->client->request('GET', '/catalog/lp/page-2?labels[]=label-pagination');
        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('.kb-catalog__grid article'));
        self::assertSelectorTextContains('.kb-pagination__range', '13–13 sur 13 résultats');
    }

    public function testTheFirstPageRedirectsToTheUrlWithoutPage(): void
    {
        $this->client->request('GET', '/catalog/page-1');
        self::assertResponseRedirects('/catalog', Response::HTTP_MOVED_PERMANENTLY);

        $this->client->request('GET', '/catalog/lp/page-1?sort=az');
        self::assertResponseRedirects('/catalog/lp?sort=az', Response::HTTP_MOVED_PERMANENTLY);

        $this->client->request('GET', '/catalog/lp/page-0');
        self::assertResponseRedirects('/catalog/lp', Response::HTTP_MOVED_PERMANENTLY);
    }

    public function testTheEmptyFieldsOfTheFormAreLeftOutOfTheUrl(): void
    {
        $this->client->request('GET', '/catalog/lp?styles[]=punk&artistSearch=&sort=new');

        self::assertResponseRedirects('/catalog/lp?'.http_build_query(['styles' => ['punk']]));
    }

    public function testAPageOutOfRangeIsNotFound(): void
    {
        $this->client->request('GET', '/catalog/page-999');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        // Past PHP_INT_MAX: a 404 too, not the paginator's error.
        $this->client->request('GET', '/catalog/page-99999999999999999999');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testASwitchSentAsZeroIsOff(): void
    {
        $label = $this->label('Label Zéro');
        $this->release('Vendu', $label, stock: 0);
        $this->em()->flush();

        $this->assertResults(['Vendu'], '/catalog?labels[]=label-zero&hideSoldOut=0&kbrProduction=0');
    }

    public function testThePageOneRedirectKeepsTheArtistSearch(): void
    {
        $this->client->request('GET', '/catalog/lp/page-1?artistSearch=ab');

        self::assertResponseRedirects('/catalog/lp?artistSearch=ab', Response::HTTP_MOVED_PERMANENTLY);
    }

    public function testValuesOfAGroupAddUpAndGroupsNarrowEachOther(): void
    {
        $label = $this->label('Label Groupes');
        $this->release('Punk seul', $label, styles: ['Punk']);
        $this->release('Oi seul', $label, styles: ['Oi']);
        $this->release('Ska seul', $label, styles: ['Ska']);
        $this->em()->flush();

        $this->assertResults(['Oi seul', 'Punk seul'], '/catalog?labels[]=label-groupes&styles[]=punk&styles[]=oi');
        $this->assertResults(['Punk seul'], '/catalog?labels[]=label-groupes&styles[]=punk');
    }

    public function testFacetCountsApplyTheOtherGroups(): void
    {
        $label = $this->label('Label Compteurs');
        $this->release('Punk 1', $label, styles: ['Punk']);
        $this->release('Punk 2', $label, styles: ['Punk', 'Oi']);
        $this->release('Oi 1', $label, styles: ['Oi']);
        $this->em()->flush();

        // Punk chosen: the style counts ignore it, the label count applies it.
        $crawler = $this->client->request('GET', '/catalog?labels[]=label-compteurs&styles[]=punk');

        self::assertSame('2', $this->facetCount($crawler, 'styles', 'punk'));
        self::assertSame('2', $this->facetCount($crawler, 'styles', 'oi'));
        self::assertSame('2', $this->facetCount($crawler, 'labels', 'label-compteurs'));
        self::assertSelectorTextSame('.kb-catalog__count', '2 références');
    }

    public function testARecordInTwoChosenStylesAndLabelsCountsOnce(): void
    {
        $label = $this->label('Label Distinct');
        $other = $this->label('Label Distinct Bis');
        $release = $this->release('Deux styles', $label, styles: ['Punk', 'Oi']);
        $release->getAlbum()?->addLabel($other);
        $this->em()->flush();

        $crawler = $this->client->request('GET', '/catalog?labels[]=label-distinct&labels[]=label-distinct-bis&styles[]=punk&styles[]=oi');

        self::assertSelectorTextSame('.kb-catalog__count', '1 référence');
        self::assertCount(1, $crawler->filter('.kb-catalog__grid article'));
    }

    public function testHideSoldOutAndOwnProductions(): void
    {
        $label = $this->label('Label Bascules');
        $this->release('En stock', $label, stock: 3);
        $this->release('Épuisé', $label, stock: 0);
        $this->release('Production', $label, kbr: true);
        $this->em()->flush();

        $this->assertResults(['En stock', 'Production'], '/catalog?labels[]=label-bascules&hideSoldOut=1');
        $this->assertResults(['Production'], '/catalog?labels[]=label-bascules&kbrProduction=1');
    }

    public function testUnpublishedArticlesAreLeftOut(): void
    {
        $label = $this->label('Label Brouillon');
        $this->release('Publié', $label);
        $this->release('Brouillon', $label)->setPublished(false);
        $this->em()->flush();

        $this->assertResults(['Publié'], '/catalog?labels[]=label-brouillon');
    }

    public function testFanzinesFollowTheLabelFilterButNotTheRecordOnes(): void
    {
        $label = $this->label('Collectif Test');
        $this->release('Disque du collectif', $label, styles: ['Punk']);
        $this->em()->persist((new Book())->setName('Zine du collectif')->setSku('TEST-ZINE')->setPrice(300)->setStock(5)->setPublished(true)->setPublisher($label));
        $this->em()->flush();

        $this->assertResults(['Disque du collectif', 'Zine du collectif'], '/catalog?labels[]=collectif-test');
        $this->assertResults(['Disque du collectif'], '/catalog?labels[]=collectif-test&styles[]=punk');
        $this->assertResults(['Zine du collectif'], '/catalog/fanzine?labels[]=collectif-test');
    }

    public function testSortByPrice(): void
    {
        $label = $this->label('Label Tri');
        $this->release('Moyen', $label, price: 1500);
        $this->release('Cher', $label, price: 2500);
        $this->release('Bon marché', $label, price: 500);
        $this->em()->flush();

        $crawler = $this->client->request('GET', '/catalog?labels[]=label-tri&sort=price-asc');
        self::assertSame(['Bon marché', 'Moyen', 'Cher'], $this->titles($crawler));

        $crawler = $this->client->request('GET', '/catalog?labels[]=label-tri&sort=price-desc');
        self::assertSame(['Cher', 'Moyen', 'Bon marché'], $this->titles($crawler));
    }

    public function testTheConditionGroupOnlyShowsWhenSomethingIsNotNew(): void
    {
        $label = $this->label('Label État');
        $this->release('Neuf', $label);
        $this->em()->flush();

        $crawler = $this->client->request('GET', '/catalog?labels[]=label-etat');
        self::assertCount(0, $crawler->filter('fieldset[data-group="conditions"]'));

        $this->release('Occasion', $label)->setItemCondition(ItemCondition::USED);
        $this->em()->flush();

        $crawler = $this->client->request('GET', '/catalog?labels[]=label-etat');
        self::assertCount(1, $crawler->filter('fieldset[data-group="conditions"]'));
        $this->assertResults(['Occasion'], '/catalog?labels[]=label-etat&conditions[]=used');
    }

    public function testAPillLinksToTheSamePageWithoutItsFilter(): void
    {
        $label = $this->label('Label Pastille');
        $this->release('Pastille', $label, styles: ['Punk']);
        $this->em()->flush();

        $crawler = $this->client->request('GET', '/catalog?labels[]=label-pastille&styles[]=punk&sort=az');
        $pill = $crawler->filter('.kb-pill[aria-label="Retirer le filtre Style : Punk"]');

        self::assertCount(1, $pill);
        $href = (string) $pill->attr('href');
        self::assertStringStartsWith('/catalog?', $href);
        parse_str((string) parse_url($href, \PHP_URL_QUERY), $query);
        self::assertSame(['labels' => ['label-pastille'], 'sort' => 'az'], $query);
        self::assertSame('/catalog', $crawler->filter('.kb-catalog__clear')->attr('href'));
    }

    public function testNothingMatchesShowsTheEmptyStateAndTheBestFilterToDrop(): void
    {
        $label = $this->label('Label Vide');
        $this->release('Seul punk', $label, styles: ['Punk']);
        $this->release('Seul punk bis', $label, styles: ['Punk']);
        $this->em()->flush();

        $crawler = $this->client->request('GET', '/catalog?labels[]=label-vide&styles[]=punk&conditions[]=refurbished');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.kb-empty h2', 'Aucun disque ne correspond');
        self::assertSelectorTextContains('.kb-empty__drop', 'Retirer « Reconditionné »');
        self::assertSelectorTextContains('.kb-empty__drop', '2 résultats');
        self::assertCount(0, $crawler->filter('.kb-catalog__grid article'));
    }

    public function testACardOffersTheCartOrAnAlertWhenSoldOut(): void
    {
        $label = $this->label('Label Carte');
        $this->release('Dispo', $label, stock: 2);
        $this->release('Plus rien', $label, stock: 0);
        $this->em()->flush();

        $crawler = $this->client->request('GET', '/catalog?labels[]=label-carte&sort=az');
        $cards = $crawler->filter('.kb-catalog__grid article');

        self::assertCount(1, $cards->eq(0)->filter('form[action^="/cart/add/"]'));
        self::assertCount(1, $cards->eq(0)->filter('.kb-badge--new'), 'created today: new');
        self::assertStringContainsString('kb-card--sold', (string) $cards->eq(1)->attr('class'));
        self::assertCount(1, $cards->eq(1)->filter('form[action^="/wishlist/add/"]'));
        self::assertCount(0, $cards->eq(1)->filter('form[action^="/cart/add/"]'));
    }

    public function testTheResultCountEndpoint(): void
    {
        $label = $this->label('Label Compte');
        $this->release('Un', $label, styles: ['Punk']);
        $this->release('Deux', $label, styles: ['Oi']);
        $this->em()->flush();

        $this->client->request('GET', '/catalog/count?labels[]=label-compte&styles[]=punk');

        self::assertResponseIsSuccessful();
        self::assertSame(['count' => 1], json_decode((string) $this->client->getResponse()->getContent(), true));

        $this->client->request('GET', '/catalog/count/fanzine?labels[]=label-compte');
        self::assertSame(['count' => 0], json_decode((string) $this->client->getResponse()->getContent(), true));
    }

    /**
     * @param list<string> $expected titles, in any order
     */
    private function assertResults(array $expected, string $uri): void
    {
        $crawler = $this->client->request('GET', $uri);
        self::assertResponseIsSuccessful();

        $titles = $this->titles($crawler);
        sort($titles);
        sort($expected);
        self::assertSame($expected, $titles, $uri);
    }

    /**
     * @return list<string>
     */
    private function titles(Crawler $crawler): array
    {
        return $crawler->filter('.kb-catalog__grid article .kb-card__link')->each(static fn (Crawler $link): string => trim($link->text()));
    }

    private function facetCount(Crawler $crawler, string $group, string $value): string
    {
        return trim($crawler->filter(\sprintf('input[name="%s[]"][value="%s"]', $group, $value))->closest('label')?->filter('.kb-filters__count')->text() ?? '');
    }

    private function em(): EntityManagerInterface
    {
        return self::service(EntityManagerInterface::class);
    }

    private function label(string $name): Label
    {
        $label = (new Label())->setName($name);
        $this->em()->persist($label);

        return $label;
    }

    /**
     * A release whose album has no artist name to show: the card title is the album name.
     *
     * @param list<string> $styles style names, from Style::OFFICIAL
     */
    private function release(
        string $name,
        Label $label,
        array $styles = [],
        int $stock = 5,
        int $price = 1000,
        bool $kbr = false,
        ReleaseFormat $format = ReleaseFormat::CD,
    ): Release {
        $em = $this->em();
        $artist = (new Artist())->setName($name);
        $album = (new Album())->setName($name)->setArtist($artist)->setKbrProduction($kbr)->setReleaseType(AlbumReleaseType::ALBUM)->addLabel($label);
        foreach ($styles as $style) {
            $album->addStyle($em->getRepository(Style::class)->findOneBy(['name' => $style]) ?? throw new \LogicException($style));
        }
        $release = (new Release())->setName($name)->setSku('TEST-'.bin2hex(random_bytes(4)))->setPrice($price)->setStock($stock)
            ->setPublished(true)->setFormat($format)->setAlbum($album);

        $em->persist($artist);
        $em->persist($album);
        $em->persist($release);

        return $release;
    }
}
