<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Homepage;
use App\Enum\HomeShelf;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The homepage is managed from one place: « Page d'accueil » in the back-office menu.
 */
class AdminHomepageTest extends WebTestCase
{
    use OrderTestTrait;

    private KernelBrowser $client;

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

    public function testTheMenuGathersTheHomepage(): void
    {
        $crawler = $this->client->request('GET', '/admin');

        $links = $crawler->filter('a')->each(static fn ($link): string => trim(preg_replace('/\s+/', ' ', $link->text()) ?? '').' → '.parse_url((string) $link->attr('href'), \PHP_URL_PATH));
        self::assertStringContainsString('Page d\'accueil', $crawler->text());
        foreach (['Blocs de l\'accueil → /admin/homepage', 'Encart à la une → /admin/featured-release', 'Voir l\'accueil → /'] as $entry) {
            self::assertContains($entry, $links);
        }
    }

    public function testTheBlocksAreEditedInASingleForm(): void
    {
        $this->client->request('GET', '/admin/homepage');
        self::assertResponseRedirects();
        $crawler = $this->client->followRedirect();

        $form = $crawler->filter('form[name="Homepage"]')->form();
        $values = $form->getPhpValues();
        $values['Homepage']['manifestoKicker'] = 'Le collectif';
        $values['Homepage']['manifestoHeadline'] = "Pas de patron.\nPas de chef.";
        $values['Homepage']['productionsTitle'] = 'Nos sorties';
        $values['Homepage']['latestLimit'] = '12';
        $values['Homepage']['latestShelves'] = ['tout', 'cd'];
        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseRedirects();

        $this->entityManager()->clear();
        $homepage = $this->entityManager()->getRepository(Homepage::class)->findOneBy([]);
        self::assertSame('Le collectif', $homepage?->getManifestoKicker());
        self::assertSame(['Pas de patron.', 'Pas de chef.'], $homepage->getManifestoHeadlineLines());
        self::assertSame('Nos sorties', $homepage->getProductionsTitle());
        self::assertSame(12, $homepage->getLatestLimit());
        self::assertSame([HomeShelf::ALL, HomeShelf::CD], $homepage->getLatestShelves());

        $this->client->request('GET', '/');
        self::assertSelectorTextContains('#le-label', 'Pas de chef.');
    }
}
