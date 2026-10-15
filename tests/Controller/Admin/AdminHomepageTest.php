<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Homepage;
use App\Entity\Page;
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
        foreach (['Blocs de l\'accueil → /admin/homepage', 'Encart à la une → /admin/featured-release', 'Colonnes du footer → /admin/footer-column', 'Voir l\'accueil → /'] as $entry) {
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
        $values['Homepage']['stripRight'] = 'Oi! · antifa';
        $values['Homepage']['footerText'] = 'Fait à Bayonne.';
        $values['Homepage']['footerTagline'] = 'Gora herria';
        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseRedirects();

        $this->entityManager()->clear();
        $homepage = $this->entityManager()->getRepository(Homepage::class)->findOneBy([]);
        self::assertSame('Le collectif', $homepage?->getManifestoKicker());
        self::assertSame(['Pas de patron.', 'Pas de chef.'], $homepage->getManifestoHeadlineLines());
        self::assertSame('Nos sorties', $homepage->getProductionsTitle());
        self::assertSame(12, $homepage->getLatestLimit());
        self::assertSame([HomeShelf::ALL, HomeShelf::CD], $homepage->getLatestShelves());
        self::assertSame('Oi! · antifa', $homepage->getStripRight());
        self::assertSame('Fait à Bayonne.', $homepage->getFooterText());

        $this->client->request('GET', '/');
        self::assertSelectorTextContains('#le-label', 'Pas de chef.');
        self::assertSelectorTextContains('.kb-header__strip', 'Oi! · antifa');
        self::assertSelectorTextContains('.kb-footer__about', 'Fait à Bayonne.');
        self::assertSelectorTextContains('.kb-footer__copyright', 'Gora herria');
    }

    public function testFooterColumnsAreCreatedWithTheirLinks(): void
    {
        $page = $this->entityManager()->getRepository(Page::class)->findOneBy(['slug' => 'cgv']);
        $crawler = $this->client->request('GET', '/admin/footer-column/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="FooterColumn"]')->form();
        $values = $form->getPhpValues();
        $values['FooterColumn']['name'] = 'Infos';
        $values['FooterColumn']['position'] = '0';
        $values['FooterColumn']['links'] = [
            ['name' => '', 'page' => (string) $page?->getId(), 'support' => '', 'url' => '', 'position' => '1'],
            ['name' => 'Bandcamp', 'page' => '', 'support' => '', 'url' => 'https://kaleborroka.bandcamp.com', 'position' => '2'],
        ];
        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseRedirects();

        $crawler = $this->client->request('GET', '/');
        $column = $crawler->filter('footer nav.kb-footer__col')->first();
        self::assertSame('Infos', $column->filter('.kb-footer__heading')->text());
        self::assertSame(['Conditions générales de vente', 'Bandcamp'], $column->filter('a')->each(static fn ($link): string => $link->text()));
    }

    public function testAFooterLinkLeadsSomewhere(): void
    {
        $crawler = $this->client->request('GET', '/admin/footer-column/new');
        $form = $crawler->filter('form[name="FooterColumn"]')->form();
        $values = $form->getPhpValues();
        $values['FooterColumn']['name'] = 'Infos';
        $values['FooterColumn']['links'] = [
            ['name' => 'Nulle part', 'page' => '', 'support' => '', 'url' => '', 'position' => '1'],
        ];
        $crawler = $this->client->request('POST', $form->getUri(), $values);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Choisissez où mène le lien', $crawler->text());
    }
}
