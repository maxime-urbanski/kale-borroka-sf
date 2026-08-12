<?php

declare(strict_types=1);

namespace App\Tests\Controller\Catalog;

use App\Repository\EditionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumDetailsControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
    }

    /**
     * The album page is public. A non-nullable #[CurrentUser] argument used to make
     * UserValueResolver throw an AccessDeniedException here, which the firewall turned
     * into a redirect to the login page for every anonymous visitor.
     */
    public function testAnonymousVisitorCanSeeAnAlbum(): void
    {
        $this->client->request('GET', $this->firstEditionUri());

        self::assertResponseIsSuccessful();
    }

    public function testLoggedInUserCanSeeAnAlbum(): void
    {
        $user = self::getContainer()->get(UserRepository::class)->findOneBy([]);
        self::assertNotNull($user, 'the fixtures should provide at least one user');

        $this->client->loginUser($user);
        $this->client->request('GET', $this->firstEditionUri());

        self::assertResponseIsSuccessful();
    }

    /**
     * The whole point of the Edition level: two pressings of one record show up on a
     * single page behind a picker, instead of as two separate look-alike products.
     */
    public function testAlbumWithSeveralEditionsRendersAPicker(): void
    {
        $edition = self::getContainer()->get(EditionRepository::class)
            ->findOneBy(['slug' => 'quartier-maudit-lp']);
        self::assertNotNull($edition, 'la fixture edition_QuartierMaudit_LP est absente');

        $crawler = $this->client->request('GET', $this->uriFor($edition));

        self::assertResponseIsSuccessful();
        self::assertCount(
            2,
            $crawler->filter('[data-edition-picker-target="choice"]'),
            'les deux éditions de Quartier Maudit doivent apparaître dans le sélecteur'
        );
    }

    /**
     * ?edition= is what disambiguates two pressings sharing a support — both editions of
     * Quartier Maudit are LPs, so the support alone cannot pick between them.
     */
    public function testEditionQueryParameterSelectsThePressing(): void
    {
        $edition = self::getContainer()->get(EditionRepository::class)
            ->findOneBy(['slug' => 'quartier-maudit-lp-vinyle-rouge']);
        self::assertNotNull($edition);

        $crawler = $this->client->request('GET', $this->uriFor($edition));

        self::assertResponseIsSuccessful();

        $visiblePanels = $crawler->filter('[data-edition-picker-target="panel"]:not([hidden])');
        self::assertCount(1, $visiblePanels, 'une seule édition doit être affichée à la fois');
        self::assertSame(
            'quartier-maudit-lp-vinyle-rouge',
            $visiblePanels->attr('data-edition')
        );
    }

    /**
     * Two offers of the same pressing — new and second-hand — must both be selectable.
     */
    public function testEditionWithSeveralOffersListsThemAll(): void
    {
        $edition = self::getContainer()->get(EditionRepository::class)
            ->findOneBy(['slug' => 'quartier-maudit-lp']);
        self::assertNotNull($edition);

        $crawler = $this->client->request('GET', $this->uriFor($edition));

        $offers = $crawler->filter('[data-edition="quartier-maudit-lp"][data-edition-picker-target="offer"]');

        // Randomised fixtures can attach extra offers to this edition, so assert against
        // what the edition actually holds rather than a hardcoded count.
        self::assertCount(
            $edition->getArticles()->count(),
            $offers,
            'chaque offre de l\'édition doit être listée'
        );
        self::assertGreaterThanOrEqual(2, $offers->count());
        self::assertStringContainsString(
            'Occasion',
            $crawler->filter('[data-edition-picker-target="panel"][data-edition="quartier-maudit-lp"]')->text(),
            'l\'offre d\'occasion doit être distinguée de l\'offre neuve'
        );
    }

    private function uriFor(object $edition): string
    {
        return sprintf(
            '/catalog/%s/%s?edition=%s',
            $edition->getSupport()->getName(),
            $edition->getAlbum()->getSlug(),
            $edition->getSlug()
        );
    }

    private function firstEditionUri(): string
    {
        $edition = self::getContainer()->get(EditionRepository::class)->findOneBy([]);
        self::assertNotNull($edition, 'the fixtures should provide at least one edition');

        return $this->uriFor($edition);
    }
}
