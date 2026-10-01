<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Style;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Styles are a closed list (Style::OFFICIAL), seeded by a migration: albums pick from it,
 * nobody types a new one.
 */
class OfficialStylesTest extends WebTestCase
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

    public function testTheDatabaseHoldsExactlyTheOfficialStyles(): void
    {
        $names = array_map(static fn (Style $style): ?string => $style->getName(), $this->entityManager()->getRepository(Style::class)->findAll());
        sort($names);
        $official = Style::OFFICIAL;
        sort($official);

        self::assertSame($official, $names);
    }

    public function testTheAlbumFormOffersTheOfficialStylesInAMultipleSelect(): void
    {
        $crawler = $this->client->request('GET', '/admin/album/new');

        $select = $crawler->filter('select[name="Album[styles][]"]');
        self::assertCount(1, $select, 'a plain multiple select, not an autocomplete');
        self::assertSame('multiple', $select->attr('multiple'));
        self::assertNull($select->attr('data-kbr-autocomplete-create'), 'no style created on the fly');
        $options = $select->filter('option')->each(static fn ($option): string => trim($option->text()));
        // In the database's collation order, which PHP's sort() does not reproduce.
        $sorted = array_map(static fn (Style $style): ?string => $style->getName(), $this->entityManager()->getRepository(Style::class)->findBy([], ['name' => 'ASC']));
        self::assertSame($sorted, $options, 'every official style, sorted');
    }

    /**
     * The shop filter offers only the styles it would find something for.
     */
    public function testTheCatalogFilterListsOnlyStylesWithPublishedReleases(): void
    {
        // The fixtures tag albums at random: make sure two styles have none.
        $this->entityManager()->getConnection()->executeStatement("DELETE FROM album_style WHERE style_id IN (SELECT id FROM style WHERE name IN ('Crust', 'Dub'))");

        $crawler = $this->client->request('GET', '/catalog/lp/page-1');

        self::assertResponseIsSuccessful();
        $offered = $crawler->filter('input[type="checkbox"][name="globalFilters[styles][]"]')->each(
            static fn ($input): string => trim($crawler->filter(\sprintf('label[for="%s"]', $input->attr('id')))->text()),
        );
        $used = $this->entityManager()->createQuery(
            'SELECT DISTINCT style.name FROM App\Entity\Release release JOIN release.album album JOIN album.styles style WHERE release.published = true ORDER BY style.name',
        )->getSingleColumnResult();

        self::assertNotEmpty($offered);
        self::assertNotContains('CRUST', array_map(mb_strtoupper(...), $offered));
        self::assertSame(array_map(mb_strtoupper(...), $used), array_map(mb_strtoupper(...), $offered));
    }

    public function testTheStylePageIsReadOnly(): void
    {
        $crawler = $this->client->request('GET', '/admin/style');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('.action-new'), 'no « Créer » button');
        self::assertCount(0, $crawler->filter('.action-edit, .action-delete'));

        $style = $this->entityManager()->getRepository(Style::class)->findOneBy([]);
        self::assertInstanceOf(Style::class, $style);

        foreach (['/admin/style/new', \sprintf('/admin/style/%d/edit', $style->getId())] as $uri) {
            $this->client->request('GET', $uri);
            self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, $uri);
        }
    }
}
