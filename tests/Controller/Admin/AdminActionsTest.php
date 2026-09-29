<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Merch;
use App\Entity\Release;
use App\Entity\ShopSettings;
use App\Enum\OrderStatus;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * The custom back-office actions, end to end. Everything runs in a transaction rolled
 * back after each test (see OrderTestTrait).
 */
class AdminActionsTest extends WebTestCase
{
    use OrderTestTrait;

    private ?KernelBrowser $client = null;

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

    public function testDuplicatingAReleaseCreatesAnUnpublishedDraft(): void
    {
        [$original] = $this->releasesWithStock(7);
        \assert($original instanceof Release);
        $original->setColor('noir')->setGtin('3760123456789');
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', \sprintf('/admin/release/%d/edit', $original->getId()));
        $this->submitActionForm($crawler, '/duplicate');

        self::assertResponseRedirects();
        $copy = $this->entityManager()->getRepository(Release::class)->findOneBy(['name' => $original->getName().' (copie)']);
        self::assertInstanceOf(Release::class, $copy);
        self::assertStringEndsWith(\sprintf('/admin/release/%d/edit', $copy->getId()), (string) $this->client->getResponse()->headers->get('Location'));

        self::assertFalse($copy->isPublished());
        self::assertSame(0, $copy->getStock());
        self::assertNull($copy->getGtin());
        self::assertNotSame($original->getSku(), $copy->getSku());
        self::assertNotSame($original->getSlug(), $copy->getSlug());
        self::assertSame($original->getAlbum()?->getId(), $copy->getAlbum()?->getId());
        self::assertSame($original->getFormat(), $copy->getFormat());
        self::assertSame('noir', $copy->getColor());
    }

    public function testDuplicatingNeedsAPostWithAValidToken(): void
    {
        [$original] = $this->releasesWithStock(1);

        $this->client->request('GET', \sprintf('/admin/release/%d/duplicate', $original->getId()));
        self::assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);

        $this->client->request('POST', \sprintf('/admin/release/%d/duplicate?_token=forged', $original->getId()));
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testPayingAnOrderFromTheBackOfficeTakesTheStock(): void
    {
        [$release] = $this->releasesWithStock(3);
        $order = $this->placeOrder($this->user('test@test.fr'), [$release->getId() => 2]);

        $crawler = $this->client->request('GET', \sprintf('/admin/order/%d', $order->getId()));
        self::assertResponseIsSuccessful();
        // Only the transitions the workflow allows are offered.
        self::assertCount(0, $crawler->filter('form[action*="/transition/ship"]'));
        $this->submitActionForm($crawler, '/transition/pay');

        self::assertResponseRedirects();
        self::assertSame(OrderStatus::PAID, $this->reload($order)->getStatus());
        self::assertSame(1, $this->stockOf($release));
    }

    public function testPayingWithoutStockShowsAnErrorInsteadOfFailing(): void
    {
        [$release] = $this->releasesWithStock(3);
        $order = $this->placeOrder($this->user('test@test.fr'), [$release->getId() => 2]);
        $this->entityManager()->getConnection()->executeStatement('UPDATE article SET stock = 1 WHERE id = ?', [$release->getId()]);

        $crawler = $this->client->request('GET', \sprintf('/admin/order/%d', $order->getId()));
        $this->submitActionForm($crawler, '/transition/pay');
        $this->client->followRedirect();

        self::assertSelectorTextContains('.alert-danger', 'Stock insuffisant');
        self::assertSame(OrderStatus::PENDING, $this->reload($order)->getStatus());
    }

    /**
     * assets/admin.js submits a typed-in entry as "__new__:<name>".
     */
    public function testAlbumFormCreatesAMissingArtistAndReusesAnExistingOne(): void
    {
        $this->submitAlbum('Nouvel album', '__new__:Les Brigands du Sud');

        $artist = $this->entityManager()->getRepository(Artist::class)->findOneBy(['name' => 'Les Brigands du Sud']);
        self::assertInstanceOf(Artist::class, $artist);
        $album = $this->entityManager()->getRepository(Album::class)->findOneBy(['name' => 'Nouvel album']);
        self::assertSame($artist, $album?->getArtist());

        $artistCount = $this->entityManager()->getRepository(Artist::class)->count([]);
        $this->submitAlbum('Deuxième album', '__new__:Les Brigands du Sud');
        self::assertSame($artistCount, $this->entityManager()->getRepository(Artist::class)->count([]), 'an artist with that name already exists: reuse it');
    }

    /**
     * assets/admin.js only enables creation on selects carrying this attribute.
     */
    public function testCreatableAutocompletesAreFlaggedForTheScript(): void
    {
        $crawler = $this->client->request('GET', '/admin/album/new');

        foreach (['artist', 'labels', 'styles'] as $field) {
            self::assertCount(1, $crawler->filter(\sprintf('select[name^="Album[%s][autocomplete]"][data-kbr-autocomplete-create="true"]', $field)), $field);
        }

        $crawler = $this->client->request('GET', '/admin/release/new');
        self::assertCount(0, $crawler->filter('[data-kbr-autocomplete-create]'), 'albums are not created on the fly');
    }

    public function testAlbumFormCreatesItsReleases(): void
    {
        $this->submitAlbum('Album et pressages', '__new__:Groupe Test', [
            ['name' => 'LP noir', 'format' => 'vinyl_12', 'color' => 'noir', 'stock' => '10', 'price' => '20,00', 'itemCondition' => 'new'],
            ['name' => 'K7', 'format' => 'cassette', 'stock' => '5', 'price' => '8,00', 'itemCondition' => 'new'],
        ]);

        $album = $this->entityManager()->getRepository(Album::class)->findOneBy(['name' => 'Album et pressages']);
        self::assertInstanceOf(Album::class, $album);
        self::assertSame(['LP noir', 'K7'], $album->getReleases()->map(fn (Release $release) => $release->getName())->getValues());
        self::assertSame(2000, $album->getReleases()->first()->getPrice());
    }

    public function testGeneratingMerchSizes(): void
    {
        $merch = $this->entityManager()->getRepository(Merch::class)->findOneBy(['name' => 'T-shirt Brixton Cats']);
        self::assertInstanceOf(Merch::class, $merch);
        $before = $merch->getVariants()->count();

        $crawler = $this->client->request('GET', \sprintf('/admin/merch/%d/edit', $merch->getId()));
        $this->submitActionForm($crawler, '/generate-sizes');

        self::assertResponseRedirects();
        $merch = $this->reload($merch);
        // The fixtures have S, M, L, XL: only XXL is missing.
        self::assertSame($before + 1, $merch->getVariants()->count());
        self::assertFalse($merch->getVariants()->last()->isPublished());
    }

    public function testShopSettingsListGoesStraightToTheForm(): void
    {
        $settings = $this->entityManager()->getRepository(ShopSettings::class)->findOneBy([]);
        self::assertNotNull($settings);

        $this->client->request('GET', '/admin/shop-settings');

        self::assertResponseRedirects(\sprintf('http://localhost/admin/shop-settings/%d/edit', $settings->getId()));
    }

    /**
     * The kernel resets its services between requests, entity manager included, so
     * entities loaded before a request are detached afterwards.
     *
     * @template T of object
     *
     * @param T $entity
     *
     * @return T
     */
    private function reload(object $entity): object
    {
        $reloaded = $this->entityManager()->find($entity::class, $entity->getId());
        self::assertNotNull($reloaded);

        return $reloaded;
    }

    /**
     * Action buttons are small POST forms; find one by the end of its URL path.
     */
    private function submitActionForm(Crawler $crawler, string $pathSuffix): void
    {
        $forms = $crawler->filter('form[method="POST"], form[method="post"]')
            ->reduce(fn (Crawler $form) => str_contains((string) parse_url((string) $form->attr('action'), \PHP_URL_PATH), $pathSuffix));
        self::assertGreaterThan(0, $forms->count(), \sprintf('no action form posting to *%s', $pathSuffix));

        $this->client->request('POST', (string) $forms->first()->attr('action'));
    }

    /**
     * @param list<array<string, string>> $releases
     */
    private function submitAlbum(string $name, string $artist, array $releases = []): void
    {
        $crawler = $this->client->request('GET', '/admin/album/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="Album"]')->form();
        $values = $form->getPhpValues();
        $values['Album']['name'] = $name;
        $values['Album']['artist']['autocomplete'] = $artist;
        $values['Album']['releaseType'] = 'album';
        $values['Album']['kbrProduction'] = '0';
        foreach ($releases as $i => $release) {
            $values['Album']['releases'][$i] = $release;
        }

        $this->client->request('POST', $form->getUri(), $values);

        self::assertResponseRedirects(null, null, 'the album form should be valid');
    }
}
