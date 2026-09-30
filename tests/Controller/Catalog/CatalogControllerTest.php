<?php

declare(strict_types=1);

namespace App\Tests\Controller\Catalog;

use App\Enum\SupportType;
use App\Repository\SupportRepository;
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CatalogControllerTest extends WebTestCase
{
    use ServiceTrait;

    private KernelBrowser $client;
    private const DEFAULT_URI = '/catalog';
    private const ERROR_SUPPORT = '/cdlp';

    public function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
    }

    public function testCatalogPage(): void
    {
        $crawler = $this->client->request('GET', self::DEFAULT_URI);
        self::assertResponseIsSuccessful();

        $supportRepository = self::service(SupportRepository::class);
        $supports = $supportRepository->findAll();

        $liSupport = $crawler->filter('.list-support');
        self::assertCount(count($supports), $liSupport, 'le nombre de lien ne correspond pas');

        foreach ($supports as $support) {
            self::assertSelectorTextSame(
                '.catalog-'.$support,
                strtoupper((string) $support->getName())
            );
        }
    }

    public function testAccessCatalogLpPage(): void
    {
        $this->client->request(
            'GET',
            self::DEFAULT_URI.'/lp'
        );
        self::assertResponseIsSuccessful();
    }

    public function testAccessCatalogEpPage(): void
    {
        $this->client->request(
            'GET',
            self::DEFAULT_URI.'/'.SupportType::EP->value
        );
        self::assertResponseIsSuccessful();
    }

    public function testAccessCatalogCdPage(): void
    {
        $this->client->request(
            'GET',
            self::DEFAULT_URI.'/'.SupportType::CD->value
        );
        self::assertResponseIsSuccessful();
    }

    public function testAccessCatalogFanzinePage(): void
    {
        $this->client->request(
            'GET',
            self::DEFAULT_URI.'/'.SupportType::FANZINE->value
        );
        self::assertResponseIsSuccessful();
    }

    public function testAccessCatalogTapePage(): void
    {
        $this->client->request('GET', self::DEFAULT_URI.'/'.SupportType::TAPE->value);
        self::assertResponseIsSuccessful();
    }

    /**
     * SupportType drives the route requirement; each case needs its Support row, and each
     * section should list something with the fixtures (fanzines are books, the rest releases).
     */
    public function testEverySupportTypeHasAReachableCatalogPage(): void
    {
        $supportRepository = self::service(SupportRepository::class);

        foreach (SupportType::cases() as $supportType) {
            self::assertNotNull(
                $supportRepository->findOneBy(['code' => $supportType]),
                \sprintf('no Support row for SupportType::%s', $supportType->name),
            );

            $crawler = $this->client->request('GET', self::DEFAULT_URI.'/'.$supportType->value);
            self::assertResponseIsSuccessful(\sprintf('/catalog/%s should be reachable', $supportType->value));
            self::assertGreaterThan(
                0,
                $crawler->filter('article')->count(),
                \sprintf('/catalog/%s should list at least one article', $supportType->value),
            );
        }
    }

    public function testAccessCatalogWithBadSupport(): void
    {
        $this->client->request('GET', self::DEFAULT_URI.self::ERROR_SUPPORT);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
