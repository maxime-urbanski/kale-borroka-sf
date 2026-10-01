<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Release;
use App\Entity\Style;
use App\Enum\AlbumReleaseType;
use App\Enum\ReleaseFormat;
use App\Repository\ReleaseRepository;
use App\Tests\Order\OrderTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

class ImportCatalogCommandTest extends KernelTestCase
{
    use OrderTestTrait;

    private const string CATALOG = __DIR__.'/../Catalog/catalog.yaml';

    /** @var list<string> */
    private array $files = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $this->beginIsolation();
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), $this->files);
        $this->endIsolation();
        parent::tearDown();
    }

    public function testImportsAlbumsWithTheirTracksAndPressings(): void
    {
        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute(['file' => self::CATALOG]), $tester->getDisplay());

        $album = $this->entityManager()->getRepository(Album::class)->findOneBy(['name' => 'Premier Essai']);
        self::assertNotNull($album);
        self::assertSame('Les Testeurs', $album->getArtist()?->getName());
        self::assertSame('FR', $album->getArtist()->getCountry());
        self::assertTrue($album->isKbrProduction());
        self::assertSame('KBR#042', $album->getKbrProductionId());
        self::assertSame('2021-03-01', $album->getDateRelease()?->format('Y-m-d'));
        self::assertSame(['Ouverture', 'Final'], $album->getTracklists()->map(static fn ($song) => $song->getName())->getValues());
        self::assertSame([154, 61], $album->getTracklists()->map(static fn ($song) => $song->getDuration())->getValues());
        self::assertSame(['A1', 'B1'], $album->getTracklists()->map(static fn ($song) => $song->getPosition())->getValues());
        self::assertCount(2, $album->getLabels());
        self::assertSame(['Punk', 'Street Punk'], $album->getStyles()->map(static fn (Style $style) => $style->getName())->getValues());

        $black = $this->release('TEST-PE-LP');
        self::assertSame('Les Testeurs - Premier Essai', $black->getName());
        self::assertSame(ReleaseFormat::VINYL_12, $black->getFormat());
        self::assertSame(2000, $black->getPrice(), 'Default price of the format.');
        self::assertSame(3, $black->getStock());
        self::assertTrue($black->isPublished());
        self::assertSame('Kale Borroka Records', $black->getLabel()?->getName());

        $red = $this->release('TEST-PE-LP-RED');
        self::assertSame(2500, $red->getPrice());
        self::assertSame(100, $red->getLimitedTo());
        self::assertSame('édition limitée', $red->getEditionLabel());

        $compilation = $this->release('TEST-CDT-7')->getAlbum();
        self::assertNotNull($compilation);
        self::assertSame(AlbumReleaseType::COMPILATION, $compilation->getReleaseType());
        self::assertSame(
            ['Les Testeurs', 'Groupe Invité'],
            $compilation->getTracklists()->map(static fn ($song) => $song->getArtist()->first() ?: null)->map(static fn (?Artist $artist) => $artist?->getName())->getValues(),
            'Each track is credited to its own artist, created bare when not described.',
        );
    }

    public function testRunningItAgainKeepsWhatIsThere(): void
    {
        $this->tester()->execute(['file' => self::CATALOG]);
        $this->release('TEST-PE-LP')->setStock(1)->setPrice(1800);
        $this->entityManager()->flush();

        $tester = $this->tester();
        self::assertSame(Command::SUCCESS, $tester->execute(['file' => self::CATALOG]));

        self::assertSame(1, $this->release('TEST-PE-LP')->getStock(), 'A sale is not undone.');
        self::assertSame(1800, $this->release('TEST-PE-LP')->getPrice());
        self::assertCount(1, $this->entityManager()->getRepository(Artist::class)->findBy(['name' => 'Les Testeurs']));
        self::assertCount(1, $this->entityManager()->getRepository(Album::class)->findBy(['name' => 'Premier Essai']));
        self::assertCount(2, $this->entityManager()->getRepository(Album::class)->findOneBy(['name' => 'Premier Essai'])?->getTracklists() ?? []);
        self::assertStringContainsString('disque TEST-PE-LP', $tester->getDisplay());
    }

    public function testANewPressingOfAnExistingAlbumIsAdded(): void
    {
        $this->tester()->execute(['file' => self::CATALOG]);
        $file = $this->catalogWith(static function (array $catalog): array {
            $catalog['albums'][0]['releases'][] = ['sku' => 'TEST-PE-7', 'format' => 'vinyl_7', 'stock' => 4];

            return $catalog;
        });

        self::assertSame(Command::SUCCESS, $this->tester()->execute(['file' => $file]));

        self::assertSame('Premier Essai', $this->release('TEST-PE-7')->getAlbum()?->getName());
        self::assertCount(3, $this->entityManager()->getRepository(Album::class)->findOneBy(['name' => 'Premier Essai'])?->getReleases() ?? []);
    }

    public function testAnAlbumRenamedInTheBackOfficeIsNotCreatedAgain(): void
    {
        $this->tester()->execute(['file' => self::CATALOG]);
        $this->release('TEST-PE-LP')->getAlbum()?->setName('Premier essai (réédition)');
        $this->entityManager()->flush();

        self::assertSame(Command::SUCCESS, $this->tester()->execute(['file' => self::CATALOG]));

        self::assertNull($this->entityManager()->getRepository(Album::class)->findOneBy(['name' => 'Premier Essai']));
        self::assertSame('Premier essai (réédition)', $this->release('TEST-PE-LP-RED')->getAlbum()?->getName());
    }

    public function testNamesAreMatchedWhateverTheirCase(): void
    {
        $file = $this->catalogWith(static function (array $catalog): array {
            $catalog['albums'][0]['styles'] = ['punk', 'STREET PUNK'];
            $catalog['albums'][1]['styles'] = ['street punk'];
            $catalog['labels']['same_label'] = ['name' => 'kale borroka records'];
            $catalog['albums'][1]['labels'] = ['same_label'];

            return $catalog;
        });

        self::assertSame(Command::SUCCESS, $this->tester()->execute(['file' => $file]));

        self::assertSame(\count(Style::OFFICIAL), $this->entityManager()->getRepository(Style::class)->count([]), 'no style created');
        $compilation = $this->release('TEST-CDT-7')->getAlbum();
        self::assertNotNull($compilation);
        self::assertSame(['Street Punk'], $compilation->getStyles()->map(static fn (Style $style) => $style->getName())->getValues());
        self::assertSame(
            $this->release('TEST-PE-LP')->getLabel(),
            $this->release('TEST-CDT-7')->getAlbum()?->getLabels()->first(),
            'Two keys naming the same label share one row.',
        );
    }

    public function testDryRunWritesNothing(): void
    {
        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute(['file' => self::CATALOG, '--dry-run' => true]));

        self::assertNull(self::service(ReleaseRepository::class)->findOneBy(['sku' => 'TEST-PE-LP']));
        self::assertNull($this->entityManager()->getRepository(Artist::class)->findOneBy(['name' => 'Les Testeurs']));
        self::assertStringContainsString('Simulation', $tester->getDisplay());
    }

    /**
     * @return iterable<string, array{\Closure(array<string, mixed>): array<string, mixed>, string}>
     */
    public static function invalidCatalogs(): iterable
    {
        yield 'misspelt key' => [static function (array $catalog): array {
            $catalog['albums'][0]['releases'][0]['colour'] = 'noir';

            return $catalog;
        }, 'albums[0].releases[0] : clé(s) inconnue(s) « colour »'];

        yield 'unknown format' => [static function (array $catalog): array {
            $catalog['albums'][0]['releases'][0]['format'] = 'lp';

            return $catalog;
        }, '« lp » inconnu'];

        yield 'no price for the format' => [static function (array $catalog): array {
            $catalog['albums'][0]['releases'][0]['format'] = 'cd';

            return $catalog;
        }, 'pas de prix'];

        yield 'unknown artist key' => [static function (array $catalog): array {
            $catalog['albums'][0]['artist'] = 'personne';

            return $catalog;
        }, 'artiste « personne » absent'];

        yield 'duplicate SKU' => [static function (array $catalog): array {
            $catalog['albums'][1]['releases'][0]['sku'] = 'TEST-PE-LP';

            return $catalog;
        }, 'SKU « TEST-PE-LP » en double'];

        yield 'link that is no URL' => [static function (array $catalog): array {
            $catalog['artists']['les_testeurs']['links'] = ['lestesteurs.bandcamp'];

            return $catalog;
        }, 'artists.les_testeurs.links[0] : Adresse invalide'];

        yield 'barcode used twice' => [static function (array $catalog): array {
            $catalog['albums'][0]['releases'][0]['gtin'] = '4026763121406';
            $catalog['albums'][1]['releases'][0]['gtin'] = '4026763121406';

            return $catalog;
        }, 'albums[1].releases[0].gtin : code-barres « 4026763121406 » déjà utilisé'];

        yield 'country that is no code' => [static function (array $catalog): array {
            $catalog['artists']['les_testeurs']['country'] = 'France';

            return $catalog;
        }, 'artists.les_testeurs.country : Pays inconnu'];

        yield 'label website that is no URL' => [static function (array $catalog): array {
            $catalog['labels']['test_label']['website'] = 'label-de-test';

            return $catalog;
        }, 'labels.test_label.website : Adresse invalide'];

        yield 'date out of range' => [static function (array $catalog): array {
            $catalog['albums'][0]['date'] = '2021-13';

            return $catalog;
        }, 'albums[0].date : date attendue'];

        yield 'misspelt track artist key' => [static function (array $catalog): array {
            $catalog['albums'][1]['tracks'][0]['artist'] = 'les_testeur';

            return $catalog;
        }, 'albums[1].tracks[0].artist : artiste « les_testeur » absent'];

        yield 'unquoted decimal' => [static function (array $catalog): array {
            $catalog['albums'][0]['releases'][0]['catalogNumber'] = 1.10;

            return $catalog;
        }, 'catalogNumber : nombre à virgule'];

        yield 'style outside the official list' => [static function (array $catalog): array {
            $catalog['albums'][0]['styles'] = ['Punk', 'Vaporwave'];

            return $catalog;
        }, 'albums[0].styles[1] : style « Vaporwave » absent de la liste officielle'];

        yield 'style in the database but not official' => [static function (array $catalog): array {
            $catalog['albums'][0]['styles'] = ['Rap'];

            return $catalog;
        }, 'albums[0].styles[0] : style « Rap » absent de la liste officielle'];

        yield 'position that is no side and number' => [static function (array $catalog): array {
            $catalog['albums'][0]['tracks'][0]['position'] = 'Face A';

            return $catalog;
        }, 'albums[0].tracks[0].position : Position : une lettre de face puis un numéro'];

        yield 'invalid pressing after one already taken' => [static function (array $catalog): array {
            // The first SKU is a fanzine's: skipped, it must not shift the next one's name.
            array_unshift($catalog['albums'][0]['releases'], ['sku' => 'KBR-BOOK-1', 'format' => 'vinyl_12', 'stock' => 1]);
            $catalog['albums'][0]['releases'][2]['gtin'] = '12AB';

            return $catalog;
        }, 'albums[0].releases[2].gtin : Un code-barres'];

        yield 'invalid barcode' => [static function (array $catalog): array {
            $catalog['albums'][0]['releases'][0]['gtin'] = '12AB';

            return $catalog;
        }, 'albums[0].releases[0].gtin : Un code-barres'];
    }

    /**
     * @param \Closure(array<string, mixed>): array<string, mixed> $break
     */
    #[DataProvider('invalidCatalogs')]
    public function testAnInvalidCatalogImportsNothing(\Closure $break, string $error): void
    {
        // A style row left over outside the list (a database not migrated yet, say).
        $this->entityManager()->getConnection()->executeStatement("INSERT INTO style (id, name) VALUES (nextval('style_id_seq'), 'Rap')");

        $tester = $this->tester();

        self::assertSame(Command::FAILURE, $tester->execute(['file' => $this->catalogWith($break)]));

        self::assertStringContainsString($error, preg_replace('/\s+/', ' ', $tester->getDisplay()) ?? '');
        self::assertNull($this->entityManager()->getRepository(Artist::class)->findOneBy(['name' => 'Les Testeurs']));
    }

    /**
     * The file meant for production: a mistake in it would only show when deploying.
     */
    public function testTheLabelStockFileImports(): void
    {
        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute(['--dry-run' => true]), $tester->getDisplay());
    }

    private function release(string $sku): Release
    {
        $release = self::service(ReleaseRepository::class)->findOneBy(['sku' => $sku]);
        self::assertNotNull($release, $sku.' imported');

        return $release;
    }

    /**
     * @param \Closure(array<string, mixed>): array<string, mixed> $change
     */
    private function catalogWith(\Closure $change): string
    {
        $catalog = Yaml::parseFile(self::CATALOG);
        self::assertIsArray($catalog);
        $file = tempnam(sys_get_temp_dir(), 'catalog');
        self::assertIsString($file);
        $this->files[] = $file;
        file_put_contents($file, Yaml::dump($change($catalog), 6));

        return $file;
    }

    private function tester(): CommandTester
    {
        self::assertNotNull(self::$kernel);

        return new CommandTester((new Application(self::$kernel))->find('app:catalog:import'));
    }
}
