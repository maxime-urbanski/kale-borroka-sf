<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Album;
use App\Repository\AlbumRepository;
use App\Repository\ArtistRepository;
use App\Repository\SupportRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The album form is three levels deep — album, its editions, their offers — and it is the
 * only place where a whole record is created in one submit. Rendering it proves nothing:
 * what breaks lives on the write path (cascades, blank rows, validation), so these tests
 * actually POST it.
 */
class AlbumNestedFormTest extends WebTestCase
{
    /** Prefix used to find back and delete everything these tests create. */
    private const string NAME_PREFIX = 'Zz Test Une Passe';

    private ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();

        $admin = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'maxiloud@gmail.com']);
        self::assertNotNull($admin, 'la fixture user_admin est absente de la base de test');

        $this->client->loginUser($admin);
    }

    /**
     * The test database is shared between test cases and album slugs are unique, so what a
     * submit created has to go away again.
     */
    protected function tearDown(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        // A failed submit can leave the manager closed; let that failure be the one reported
        // instead of burying it under a cleanup error.
        if (!$em->isOpen()) {
            parent::tearDown();

            return;
        }

        foreach ($this->albumRepository()->createQueryBuilder('a')
            ->where('a.name LIKE :prefix')
            ->setParameter('prefix', self::NAME_PREFIX.'%')
            ->getQuery()
            ->getResult() as $album) {
            $em->remove($album);
        }

        $em->flush();

        parent::tearDown();
    }

    /**
     * The blank pressing and the blank offer are opened for convenience, not to be saved:
     * an admin who only wants the album must not have to empty them first.
     */
    public function testUntouchedNestedRowsAreDropped(): void
    {
        $name = self::NAME_PREFIX.' Album Seul';

        $this->submitAlbumForm(['name' => $name]);

        self::assertResponseRedirects();

        $album = $this->findAlbum($name);
        self::assertCount(0, $album->getEditions(), 'une édition vierge ne doit pas être enregistrée');
    }

    /**
     * The one submit that has to work: album, pressing and offer created together. Without
     * cascade: ['persist'] on Album::$editions and Edition::$articles, the flush blows up
     * with "A new entity was found through the relationship".
     */
    public function testAlbumEditionAndOfferAreCreatedInOneSubmit(): void
    {
        $name = self::NAME_PREFIX.' Album Complet';

        $this->submitAlbumForm([
            'name' => $name,
            'editions' => [0 => [
                'name' => 'LP vinyle rouge',
                'support' => (string) $this->supportId(),
                'color' => 'rouge',
                'articles' => [0 => ['price' => '12.50', 'quantity' => '3']],
            ]],
        ]);

        self::assertResponseRedirects();

        $album = $this->findAlbum($name);
        self::assertCount(1, $album->getEditions());

        $edition = $album->getEditions()->first();
        self::assertSame('LP vinyle rouge', $edition->getName());
        self::assertNotNull($edition->getSupport());
        self::assertCount(1, $edition->getArticles());

        $article = $edition->getArticles()->first();
        self::assertSame(1250, $article->getPrice(), 'le prix est stocké en centimes');
        self::assertSame(3, $article->getQuantity());
    }

    /**
     * A half-filled pressing is not blank, so it is validated instead of dropped — and the
     * admin gets a French message rather than a NOT NULL violation.
     */
    public function testHalfFilledEditionIsRejectedWithAReadableMessage(): void
    {
        $name = self::NAME_PREFIX.' Album Incomplet';

        $this->submitAlbumForm([
            'name' => $name,
            'editions' => [0 => ['name' => 'LP', 'support' => '']],
        ]);

        self::assertStringContainsString('Choisissez un support.', (string) $this->client->getResponse()->getContent());
        self::assertNull(
            $this->albumRepository()->findOneBy(['name' => $name]),
            "l'album ne doit pas être enregistré tant qu'une édition est incomplète"
        );
    }

    /**
     * An offer with a stock but no price is half-filled too.
     */
    public function testHalfFilledOfferIsRejectedWithAReadableMessage(): void
    {
        $name = self::NAME_PREFIX.' Album Sans Prix';

        $this->submitAlbumForm([
            'name' => $name,
            'editions' => [0 => [
                'name' => 'LP',
                'support' => (string) $this->supportId(),
                'articles' => [0 => ['quantity' => '5']],
            ]],
        ]);

        self::assertStringContainsString('Indiquez un prix.', (string) $this->client->getResponse()->getContent());
        self::assertNull($this->albumRepository()->findOneBy(['name' => $name]));
    }

    /**
     * Posted as a raw payload rather than through DomCrawler: the artist and label widgets
     * are autocompletes, whose <select> carries no option until one is picked, and DomCrawler
     * refuses to set a value it cannot find among the options.
     *
     * @param array<string, mixed> $albumValues
     */
    private function submitAlbumForm(array $albumValues): void
    {
        $crawler = $this->client->request('GET', '/admin/album/new');

        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form#new-Album-form');
        $payload = ['Album' => array_replace_recursive($this->emptyAlbumPayload($crawler), $albumValues)];
        $payload['ea'] = ['newForm' => ['btn' => 'saveAndReturn']];

        $this->client->request('POST', (string) $form->attr('action'), $payload);
    }

    /**
     * What the browser would send if nothing was typed: every field present and empty, the
     * pre-opened edition and offer included, with the two enum selects on the value their
     * option is actually rendered with (EasyAdmin numbers enum choices).
     *
     * @return array<string, mixed>
     */
    private function emptyAlbumPayload(Crawler $crawler): array
    {
        return [
            '_token' => $crawler->filter('form#new-Album-form input[name="Album[_token]"]')->attr('value'),
            'name' => '',
            'artist' => ['autocomplete' => (string) $this->artistId()],
            'styles' => [],
            'labels' => ['autocomplete' => []],
            'note' => '',
            'productionType' => '',
            'date_release' => '',
            'recordingYear' => '',
            'countryOfOrigin' => '',
            'duration' => '',
            'kbrProduction' => '1',
            'kbrProductionId' => '',
            'editions' => [0 => [
                'name' => '',
                'support' => '',
                'color' => '',
                'editionLabel' => '',
                'catalogNumber' => '',
                'pressingRun' => '',
                'releaseDate' => '',
                'description' => '',
                'images' => [],
                'articles' => [0 => [
                    'condition' => $this->selectedOption($crawler, '#Album_editions_0_articles_0_condition'),
                    'availability' => $this->selectedOption($crawler, '#Album_editions_0_articles_0_availability'),
                    'price' => '',
                    'quantity' => '',
                    'weight' => '',
                    'availableFrom' => '',
                    'sku' => '',
                    'gtin13' => '',
                    'description' => '',
                ]],
            ]],
        ];
    }

    private function selectedOption(Crawler $crawler, string $selector): string
    {
        $selected = $crawler->filter($selector.' option[selected]');

        return (string) ($selected->count() > 0 ? $selected->attr('value') : $crawler->filter($selector.' option')->last()->attr('value'));
    }

    private function findAlbum(string $name): Album
    {
        // The submit went through another entity manager state; drop what this one holds.
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        $album = $this->albumRepository()->findOneBy(['name' => $name]);
        self::assertNotNull($album, sprintf('l\'album "%s" n\'a pas été enregistré', $name));

        return $album;
    }

    private function albumRepository(): AlbumRepository
    {
        return self::getContainer()->get(AlbumRepository::class);
    }

    private function artistId(): int
    {
        $artist = self::getContainer()->get(ArtistRepository::class)->findOneBy([]);
        self::assertNotNull($artist, 'les fixtures artistes sont absentes de la base de test');

        return (int) $artist->getId();
    }

    private function supportId(): int
    {
        $support = self::getContainer()->get(SupportRepository::class)->findOneBy(['name' => 'lp']);
        self::assertNotNull($support, 'la fixture support_LP est absente de la base de test');

        return (int) $support->getId();
    }
}
