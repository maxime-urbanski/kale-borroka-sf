<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Enum\SupportType;
use App\Repository\SupportRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CatalogControllerTest extends WebTestCase
{
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

        $supportRepository = self::getContainer()->get(SupportRepository::class);
        $supports = $supportRepository->findAll();

        $liSupport = $crawler->filter('.list-support');
        self::assertCount(count($supports), $liSupport, 'le nombre de lien ne correspond pas');

        foreach ($supports as $support) {
            self::assertSelectorTextSame(
                '.catalog-'.$support,
                strtoupper($support->getname())
            );
        }
    }

    public function testAccessCatalogLpPage(): void
    {
        $this->client->request(
            'GET',
            self::DEFAULT_URI.'/'.SupportType::LP->value
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

    public function testAccessCatalogWithBadSupport(): void
    {
        $this->client->request('GET', self::DEFAULT_URI.self::ERROR_SUPPORT);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
