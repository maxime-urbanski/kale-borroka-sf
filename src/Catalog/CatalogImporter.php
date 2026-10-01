<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Entity\Album;
use App\Entity\Article;
use App\Entity\Artist;
use App\Entity\Label;
use App\Entity\Release;
use App\Entity\Song;
use App\Entity\Style;
use App\Entity\Support;
use App\Enum\AlbumReleaseType;
use App\Enum\ItemCondition;
use App\Enum\ReleaseFormat;
use App\Enum\SupportType;
use Doctrine\DBAL\Exception as DatabaseException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads the label's stock from a YAML file (data/catalog/stock.yaml) into the catalogue: this is
 * how the production database gets its records, Alice fixtures being dev/test only.
 *
 * Create-only, hence safe to run again: catalogue sections are matched by code, labels and
 * artists by name, styles by name among the official ones (an unknown style is an error), albums by artist and name, releases by SKU, and whatever already exists is
 * left untouched — in particular the stock, which sales have changed since. Every new entity is
 * validated before anything is written, and the whole file goes in one transaction.
 */
final readonly class CatalogImporter
{
    private const array ROOT_KEYS = ['prices', 'labels', 'artists', 'albums'];
    private const array LABEL_KEYS = ['name', 'website', 'distro'];
    private const array ARTIST_KEYS = ['name', 'country', 'links', 'description'];
    private const array ALBUM_KEYS = ['artist', 'name', 'type', 'date', 'production', 'styles', 'labels', 'note', 'tracks', 'releases'];
    private const array TRACK_KEYS = ['title', 'duration', 'artist'];
    private const array RELEASE_KEYS = ['sku', 'name', 'format', 'stock', 'price', 'color', 'edition', 'limitedTo', 'pressingYear', 'catalogNumber', 'label', 'gtin', 'condition', 'published'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @param bool $dryRun validate and write everything, then roll back
     *
     * @throws CatalogImportException when the file is malformed or an entity is invalid
     * @throws DatabaseException      when the database refuses a value the validation does not cover
     */
    public function import(string $path, bool $dryRun = false): CatalogImportReport
    {
        if (!is_file($path)) {
            throw new CatalogImportException(\sprintf('Fichier introuvable : %s', $path));
        }

        try {
            $data = Yaml::parseFile($path, Yaml::PARSE_DATETIME);
        } catch (ParseException $exception) {
            throw new CatalogImportException($exception->getMessage(), 0, $exception);
        }

        if (!\is_array($data)) {
            throw new CatalogImportException(\sprintf('%s : le fichier ne décrit aucun catalogue.', $path));
        }

        $report = new CatalogImportReport();
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $this->load(new CatalogNode($data, ''), $report);
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            $this->entityManager->clear();

            throw $exception;
        }

        if ($dryRun) {
            $connection->rollBack();
            $this->entityManager->clear();
        } else {
            $connection->commit();
        }

        return $report;
    }

    private function load(CatalogNode $root, CatalogImportReport $report): void
    {
        $root->allowOnly(self::ROOT_KEYS);
        $this->ensureSupports($report);

        $state = new CatalogImportState($this->prices($root->node('prices')));
        $errors = [];

        foreach ($root->nodes('labels') as $key => $node) {
            $state->labels[$key] = $this->label($node, $state, $report, $errors);
        }

        foreach ($root->nodes('artists') as $key => $node) {
            $state->artists[$key] = $this->artist($node, $state, $report, $errors);
        }

        foreach ($root->items('albums') as $node) {
            $this->album($node, $state, $report, $errors);
        }

        if ([] !== $errors) {
            throw CatalogImportException::withErrors($errors);
        }
    }

    /**
     * The menu, the footer and the catalogue pages list the sections from the database, and
     * no migration creates them.
     */
    private function ensureSupports(CatalogImportReport $report): void
    {
        $repository = $this->entityManager->getRepository(Support::class);

        foreach (SupportType::cases() as $code) {
            if (null === $repository->findOneBy(['code' => $code])) {
                $this->entityManager->persist((new Support())->setName($code->value)->setCode($code));
                $report->created('rayon');
            }
        }
    }

    /**
     * @return array<string, int> price in cents by ReleaseFormat value
     */
    private function prices(CatalogNode $node): array
    {
        $prices = [];

        foreach (ReleaseFormat::cases() as $format) {
            $price = $node->optionalInt($format->value);

            if (null !== $price && $price < 0) {
                throw $node->error('prix négatif', $format->value);
            }

            if (null !== $price) {
                $prices[$format->value] = $price;
            }
        }

        $node->allowOnly(array_map(static fn (ReleaseFormat $format): string => $format->value, ReleaseFormat::cases()));

        return $prices;
    }

    /**
     * @param list<string> $errors
     */
    private function label(CatalogNode $node, CatalogImportState $state, CatalogImportReport $report, array &$errors): Label
    {
        $node->allowOnly(self::LABEL_KEYS);
        $name = $node->string('name');
        $key = mb_strtolower($name);

        // Two keys may name the same label: the first one, persisted but not flushed, is no row yet.
        if (isset($state->labelsByName[$key])) {
            return $state->labelsByName[$key];
        }

        $label = $this->findByName(Label::class, $name);

        if (null !== $label) {
            $report->skipped('label '.$name);

            return $state->labelsByName[$key] = $label;
        }

        $label = (new Label())
            ->setName($name)
            ->setWebsite($node->optionalString('website'))
            ->setIsDistro($node->bool('distro', false));
        $this->validate($label, $node->path, $errors);
        $this->entityManager->persist($label);
        $report->created('label');

        return $state->labelsByName[$key] = $label;
    }

    /**
     * @param list<string> $errors
     */
    private function artist(CatalogNode $node, CatalogImportState $state, CatalogImportReport $report, array &$errors): Artist
    {
        $node->allowOnly(self::ARTIST_KEYS);
        $name = $node->string('name');
        $artist = $this->findArtist($name, $state);

        if (null !== $artist) {
            $report->skipped('artiste '.$name);

            return $artist;
        }

        $artist = (new Artist())
            ->setName($name)
            ->setCountry($node->optionalString('country'))
            ->setLinks($node->strings('links'))
            ->setDescription($node->optionalString('description'));

        return $this->newArtist($artist, $node->path, $state, $report, $errors);
    }

    /**
     * @param list<string> $errors
     */
    private function album(CatalogNode $node, CatalogImportState $state, CatalogImportReport $report, array &$errors): void
    {
        $node->allowOnly(self::ALBUM_KEYS);
        $artistKey = $node->string('artist');
        $artist = $state->artists[$artistKey] ?? throw $node->error(\sprintf('artiste « %s » absent de la section artists', $artistKey), 'artist');
        $name = $node->string('name');
        $releases = $node->items('releases');

        if ([] === $releases) {
            throw $node->error('au moins un pressage attendu', 'releases');
        }

        if (!$state->firstTime('album', $artistKey.'|'.mb_strtolower($name))) {
            throw $node->error('album en double : mettez tous ses pressages dans un même releases');
        }

        $album = $this->existingAlbum($releases, $artist, $name);

        if (null === $album) {
            $album = $this->newAlbum($node, $artist, $name, $state, $report, $errors);
        } else {
            $report->skipped('album '.$album->fullName());
        }

        $newAlbum = null === $album->getId();

        foreach ($releases as $releaseNode) {
            $release = $this->release($releaseNode, $album, $state, $report);

            if (!$newAlbum && null !== $release) {
                $this->validate($release, $releaseNode->path, $errors);
            }
        }

        if ($newAlbum) {
            $this->validate($album, $node->path, $errors);
        }
    }

    /**
     * The album of a pressing already imported comes first: it is still found once the album or
     * its artist has been renamed in the back office, where artist + name would create a copy.
     *
     * @param list<CatalogNode> $releases
     */
    private function existingAlbum(array $releases, Artist $artist, string $name): ?Album
    {
        foreach ($releases as $releaseNode) {
            $release = $this->entityManager->getRepository(Release::class)->findOneBy(['sku' => $releaseNode->string('sku')]);

            if (null !== $release?->getAlbum()) {
                return $release->getAlbum();
            }
        }

        // An unsaved artist cannot be queried on, and has no album yet anyway.
        return null === $artist->getId() ? null : $this->findByName(Album::class, $name, ['artist' => $artist]);
    }

    /**
     * @param list<string> $errors
     */
    private function newAlbum(CatalogNode $node, Artist $artist, string $name, CatalogImportState $state, CatalogImportReport $report, array &$errors): Album
    {
        $production = $node->optionalString('production');

        if (null !== $production && 1 !== preg_match('/^KBR#\d{3}$/', $production)) {
            throw $node->error('numéro de production attendu sous la forme KBR#001', 'production');
        }

        $album = (new Album())
            ->setArtist($artist)
            ->setName($name)
            ->setReleaseType($this->enum(AlbumReleaseType::class, $node, 'type') ?? AlbumReleaseType::ALBUM)
            ->setDateRelease($this->date($node))
            ->setKbrProduction(null !== $production)
            ->setKbrProductionId($production)
            ->setNote($node->optionalString('note'));

        foreach ($node->strings('styles') as $index => $styleName) {
            $album->addStyle($this->style($styleName, $state) ?? throw $node->error(\sprintf('style « %s » absent de la liste officielle', $styleName), \sprintf('styles[%d]', $index)));
        }

        foreach ($node->strings('labels') as $index => $labelKey) {
            $album->addLabel($state->labels[$labelKey] ?? throw $node->error(\sprintf('label « %s » absent de la section labels', $labelKey), \sprintf('labels[%d]', $index)));
        }

        foreach ($node->items('tracks') as $index => $trackNode) {
            $trackNode->allowOnly(self::TRACK_KEYS);
            $song = (new Song())
                ->setName($trackNode->string('title'))
                ->setTrack($index + 1)
                ->setDuration($this->duration($trackNode))
                ->addArtist($this->trackArtist($trackNode, $state, $report, $errors) ?? $artist);
            $album->addTracklist($song);
            $this->entityManager->persist($song);
            $report->created('morceau');
        }

        $this->entityManager->persist($album);
        $report->created('album');

        return $album;
    }

    private function release(CatalogNode $node, Album $album, CatalogImportState $state, CatalogImportReport $report): ?Release
    {
        $node->allowOnly(self::RELEASE_KEYS);
        $sku = $node->string('sku');

        if (!$state->firstTime('sku', $sku)) {
            throw $node->error(\sprintf('SKU « %s » en double dans le fichier', $sku), 'sku');
        }

        if (null !== $this->entityManager->getRepository(Article::class)->findOneBy(['sku' => $sku])) {
            $report->skipped('disque '.$sku);

            return null;
        }

        $gtin = $node->optionalString('gtin');

        // Unique column: caught here, it names the entry instead of failing the flush.
        if (null !== $gtin && (!$state->firstTime('gtin', $gtin) || null !== $this->entityManager->getRepository(Article::class)->findOneBy(['gtin' => $gtin]))) {
            throw $node->error(\sprintf('code-barres « %s » déjà utilisé', $gtin), 'gtin');
        }

        $format = $this->enum(ReleaseFormat::class, $node, 'format') ?? throw $node->error('valeur obligatoire', 'format');
        $price = $node->optionalInt('price') ?? $state->prices[$format->value] ?? throw $node->error(\sprintf('pas de prix, ni de prix par défaut pour %s dans prices', $format->value), 'price');
        $labelKey = $node->optionalString('label');

        $release = (new Release())
            ->setFormat($format)
            ->setEditionLabel($node->optionalString('edition'))
            ->setLimitedTo($node->optionalInt('limitedTo'))
            ->setPressingYear($node->optionalInt('pressingYear'))
            ->setCatalogNumber($node->optionalString('catalogNumber'))
            ->setLabel(null === $labelKey ? null : ($state->labels[$labelKey] ?? throw $node->error(\sprintf('label « %s » absent de la section labels', $labelKey), 'label')))
            ->setName($node->optionalString('name') ?? $album->fullName())
            ->setSku($sku)
            ->setGtin($gtin)
            ->setPrice($price)
            ->setStock($node->int('stock'))
            ->setColor($node->optionalString('color'))
            ->setItemCondition($this->enum(ItemCondition::class, $node, 'condition') ?? ItemCondition::NEW)
            ->setPublished($node->bool('published', true));
        $album->addRelease($release);
        $this->entityManager->persist($release);
        $report->created('disque');

        return $release;
    }

    /**
     * A track's own artist, on compilations and splits: a key of the artists section, or else
     * the name of an artist that gets created bare. A value shaped like a key (`les_testeurs`)
     * that is no key is a typo, not a name.
     *
     * @param list<string> $errors
     */
    private function trackArtist(CatalogNode $node, CatalogImportState $state, CatalogImportReport $report, array &$errors): ?Artist
    {
        $key = $node->optionalString('artist');

        if (null === $key) {
            return null;
        }

        if (isset($state->artists[$key])) {
            return $state->artists[$key];
        }

        if (1 === preg_match('/^[a-z0-9_]+$/', $key)) {
            throw $node->error(\sprintf('artiste « %s » absent de la section artists', $key), 'artist');
        }

        return $this->findArtist($key, $state) ?? $this->newArtist((new Artist())->setName($key), $node->path, $state, $report, $errors);
    }

    private function findArtist(string $name, CatalogImportState $state): ?Artist
    {
        $key = mb_strtolower($name);

        if (!\array_key_exists($key, $state->artistsByName)) {
            $state->artistsByName[$key] = $this->findByName(Artist::class, $name);
        }

        return $state->artistsByName[$key];
    }

    /**
     * @param list<string> $errors
     */
    private function newArtist(Artist $artist, string $path, CatalogImportState $state, CatalogImportReport $report, array &$errors): Artist
    {
        $this->validate($artist, $path, $errors);
        $this->entityManager->persist($artist);
        $state->artistsByName[mb_strtolower((string) $artist->getName())] = $artist;
        $report->created('artiste');

        return $artist;
    }

    /**
     * One of the official styles (Style::OFFICIAL, seeded by a migration): never created here.
     */
    private function style(string $name, CatalogImportState $state): ?Style
    {
        $key = mb_strtolower($name);

        // The list in the code decides, whatever rows a database not migrated yet still has.
        if (!\in_array($key, array_map(mb_strtolower(...), Style::OFFICIAL), true)) {
            return null;
        }

        if (!\array_key_exists($key, $state->styles)) {
            $state->styles[$key] = $this->findByName(Style::class, $name);
        }

        return $state->styles[$key];
    }

    /**
     * `2019-05-10`, `2019-05` or `2019`: a partial date is stored as the first day of the period.
     */
    private function date(CatalogNode $node): ?\DateTime
    {
        $value = $node->get('date');

        if (null === $value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTime::createFromInterface($value);
        }

        $value = (string) (\is_int($value) || \is_string($value) ? $value : '');
        $date = match (1) {
            preg_match('/^\d{4}$/', $value) => \DateTime::createFromFormat('!Y-m-d', $value.'-01-01'),
            preg_match('/^\d{4}-\d{2}$/', $value) => \DateTime::createFromFormat('!Y-m-d', $value.'-01'),
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) => \DateTime::createFromFormat('!Y-m-d', $value),
            default => false,
        };

        // createFromFormat() rolls 2021-13 or 2021-02-30 over to the next year or month.
        if (false === $date || !str_starts_with($date->format('Y-m-d'), $value)) {
            throw $node->error('date attendue : AAAA, AAAA-MM ou AAAA-MM-JJ', 'date');
        }

        return $date;
    }

    /**
     * Seconds, or `m:ss`.
     */
    private function duration(CatalogNode $node): ?int
    {
        $value = $node->get('duration');

        if (null === $value || \is_int($value)) {
            return $value;
        }

        if (\is_string($value) && 1 === preg_match('/^(\d+):([0-5]\d)$/', $value, $matches)) {
            return (int) $matches[1] * 60 + (int) $matches[2];
        }

        throw $node->error('durée attendue en secondes ou sous la forme m:ss', 'duration');
    }

    /**
     * @template T of \BackedEnum
     *
     * @param class-string<T> $enum
     *
     * @return T|null
     */
    private function enum(string $enum, CatalogNode $node, string $key): ?\BackedEnum
    {
        $value = $node->optionalString($key);

        if (null === $value) {
            return null;
        }

        return $enum::tryFrom($value) ?? throw $node->error(\sprintf('« %s » inconnu, attendu : %s', $value, implode(', ', array_map(static fn (\BackedEnum $case): string => (string) $case->value, $enum::cases()))), $key);
    }

    /**
     * Case-insensitive, as the matching inside the file: « Punk » is the style already called « punk ».
     *
     * @template T of object
     *
     * @param class-string<T>      $class
     * @param array<string, mixed> $criteria
     *
     * @return T|null
     */
    private function findByName(string $class, string $name, array $criteria = []): ?object
    {
        $query = $this->entityManager->getRepository($class)->createQueryBuilder('entity')
            ->where('LOWER(entity.name) = :name')
            ->setParameter('name', mb_strtolower($name))
            ->setMaxResults(1);

        foreach ($criteria as $field => $value) {
            $query->andWhere(\sprintf('entity.%1$s = :%1$s', $field))->setParameter($field, $value);
        }

        $result = $query->getQuery()->getOneOrNullResult();
        \assert(null === $result || $result instanceof $class);

        return $result;
    }

    /**
     * @param list<string> $errors
     */
    private function validate(object $entity, string $path, array &$errors): void
    {
        foreach ($this->validator->validate($entity) as $violation) {
            $errors[] = \sprintf('%s.%s : %s', $path, $violation->getPropertyPath(), $violation->getMessage());
        }
    }
}
