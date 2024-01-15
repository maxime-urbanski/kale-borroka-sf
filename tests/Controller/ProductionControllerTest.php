<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Data\ArticleFilterData;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ProductionControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private Crawler $crawler;
    private const PRODUCTION_URI = '/production';

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->client = self::createClient();
        $this->crawler = $this->client->request('GET', self::PRODUCTION_URI);
    }

    public function testAccessProductionPage(): void
    {
        $this->client->request('GET', self::PRODUCTION_URI);
        self::assertResponseIsSuccessful();
    }

    public function testCheckboxProduction()
    {
        $checkbox = $this->crawler->filter('input[name="globalFilters[kbrProduction]"]');
        $isOwnProduction = $checkbox->attr('checked');

        self::assertTrue((bool) $isOwnProduction, 'la checkbox doit être checké');
    }

    public function testDisplayCorrectNumberProduction()
    {
        $badge = $this->crawler->filter('[data-test-badge="count-production"]')->first()->text();

        $filters = new ArticleFilterData();
        $filters->kbrProduction = true;

        $articleRepository = self::getContainer()->get(ArticleRepository::class);
        $ownProdArticle = $articleRepository->filterArticleQuery($filters);
        $numberProdArticle = count($ownProdArticle->getResult());

        self::assertEquals((string) $numberProdArticle.' éléments trouvés.', $badge);
    }
}
