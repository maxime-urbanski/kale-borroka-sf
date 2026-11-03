<?php

declare(strict_types=1);

namespace App\Tests\Controller\Catalog;

use App\Entity\Release;
use App\Tests\DatabaseIsolationTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * « Nos productions »: the catalogue page with the label's own productions only (KBR#),
 * at its own URL, without the section tabs nor the « Nos productions » switch.
 */
class ProductionControllerTest extends WebTestCase
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

    public function testThePageListsTheLabelsOwnProductionsOnly(): void
    {
        $expected = (int) self::service(EntityManagerInterface::class)
            ->createQuery('SELECT COUNT(r.id) FROM '.Release::class.' r JOIN r.album al WHERE r.published = true AND r.format IS NOT NULL AND al.kbrProduction = true')
            ->getSingleScalarResult();
        self::assertGreaterThan(0, $expected);

        $crawler = $this->client->request('GET', '/production');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('h1', 'Nos productions');
        self::assertSelectorTextSame('.kb-catalog__count', \sprintf('%d références', $expected));
        self::assertCount(0, $crawler->filter('.kb-tabs'), 'no section tabs');
        self::assertCount(0, $crawler->filter('input[name="kbrProduction"]'), 'no « Nos productions » switch');
        self::assertCount(1, $crawler->filter('fieldset[data-group="supports"]'));
        self::assertCount(0, $crawler->filter('input[name="supports[]"][value="fanzine"]'), 'fanzines are no productions');
        self::assertSame('/production', $crawler->filter('#catalog-filters')->attr('action'));
        // Every card is a numbered production.
        self::assertCount(
            $crawler->filter('.kb-catalog__grid article')->count(),
            $crawler->filter('.kb-catalog__grid article .kb-badge')->reduce(static fn (Crawler $badge): bool => str_starts_with($badge->text(), 'KBR#')),
        );
    }

    public function testFiltersAndPillsStayOnTheProductionsPage(): void
    {
        $crawler = $this->client->request('GET', '/production?styles[]=punk&sort=az');

        self::assertResponseIsSuccessful();
        $pill = $crawler->filter('.kb-pill[aria-label="Retirer le filtre Style : Punk"]');
        self::assertSame('/production?sort=az', $pill->attr('href'));
        self::assertSame('/production', $crawler->filter('.kb-catalog__clear')->attr('href'));
        self::assertCount(0, $crawler->filter('.kb-pill[aria-label="Retirer le filtre Nos productions"]'));
    }

    public function testTheFirstPageRedirectsToTheUrlWithoutPage(): void
    {
        $this->client->request('GET', '/production/page-1?sort=az');
        self::assertResponseRedirects('/production?sort=az', Response::HTTP_MOVED_PERMANENTLY);

        $this->client->request('GET', '/production/page-0');
        self::assertResponseRedirects('/production', Response::HTTP_MOVED_PERMANENTLY);

        // The switch is implied here: not a second URL for the same page.
        $this->client->request('GET', '/production?kbrProduction=1');
        self::assertResponseRedirects('/production');

        $this->client->request('GET', '/production/page-999');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testTheResultCountCountsProductionsOnly(): void
    {
        $crawler = $this->client->request('GET', '/production');
        $total = (int) $crawler->filter('.kb-catalog__count')->text();

        $this->client->request('GET', '/production/count');

        self::assertResponseIsSuccessful();
        self::assertSame(['count' => $total], json_decode((string) $this->client->getResponse()->getContent(), true));
        self::assertSame('/production/count', $crawler->filter('#catalog-filters')->attr('data-result-count-url-value'));
    }
}
