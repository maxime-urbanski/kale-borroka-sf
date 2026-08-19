<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Enrich Album, Artist, Label, Song and Style with the schema.org fields the catalogue
 * needs (MusicAlbum, MusicGroup, Organization, MusicRecording), and give the four
 * name-bearing entities a slug.
 *
 * Gedmo only generates slugs on persist/update, so existing rows are backfilled here in
 * SQL before the NOT NULL and UNIQUE constraints are applied.
 */
final class Version20260812120000 extends AbstractMigration
{
    /** Tables getting a Gedmo slug derived from their `name` column. */
    private const array SLUGGED_TABLES = ['album', 'artist', 'label', 'style'];

    public function getDescription(): string
    {
        return 'Add schema.org fields and slugs to album, artist, label, song and style';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE album ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE album ADD production_type VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE album ADD recording_year SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE album ADD country_of_origin VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE album ADD duration INT DEFAULT NULL');

        $this->addSql('ALTER TABLE artist ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE artist ADD description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE artist ADD country_of_origin VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE artist ADD founded_year SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE artist ADD links JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE artist ADD image_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE artist ADD image_size INT DEFAULT NULL');
        $this->addSql('ALTER TABLE artist ADD image_updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        $this->addSql('ALTER TABLE label ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE label ADD url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE label ADD description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE label ADD country VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE label ADD logo_size INT DEFAULT NULL');
        $this->addSql('ALTER TABLE label ADD logo_updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        $this->addSql('ALTER TABLE song ADD duration INT DEFAULT NULL');
        $this->addSql('ALTER TABLE song ADD side VARCHAR(4) DEFAULT NULL');
        $this->addSql('ALTER TABLE song ADD isrc VARCHAR(12) DEFAULT NULL');

        $this->addSql('ALTER TABLE style ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE style ADD description TEXT DEFAULT NULL');

        $this->backfillSlugs();

        foreach (self::SLUGGED_TABLES as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ALTER slug SET NOT NULL', $table));
        }

        $this->addSql('CREATE UNIQUE INDEX UNIQ_39986E43989D9B62 ON album (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1599687989D9B62 ON artist (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EA750E8989D9B62 ON label (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_33BDB86A989D9B62 ON style (slug)');

        // style.name becomes unique — duplicates would silently break the new index.
        $this->addSql(<<<'SQL'
            DO $$
            BEGIN
                IF EXISTS (SELECT 1 FROM style GROUP BY name HAVING count(*) > 1) THEN
                    RAISE EXCEPTION 'style.name holds duplicates; merge them before migrating';
                END IF;
            END $$;
            SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_33BDB86A5E237E06 ON style (name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_33BDB86A5E237E06');
        $this->addSql('DROP INDEX UNIQ_33BDB86A989D9B62');
        $this->addSql('DROP INDEX UNIQ_EA750E8989D9B62');
        $this->addSql('DROP INDEX UNIQ_1599687989D9B62');
        $this->addSql('DROP INDEX UNIQ_39986E43989D9B62');

        $this->addSql('ALTER TABLE style DROP slug, DROP description');
        $this->addSql('ALTER TABLE song DROP duration, DROP side, DROP isrc');
        $this->addSql('ALTER TABLE label DROP slug, DROP url, DROP description, DROP country, DROP logo_size, DROP logo_updated_at');
        $this->addSql('ALTER TABLE artist DROP slug, DROP description, DROP country_of_origin, DROP founded_year, DROP links, DROP image_name, DROP image_size, DROP image_updated_at');
        $this->addSql('ALTER TABLE album DROP slug, DROP production_type, DROP recording_year, DROP country_of_origin, DROP duration');
    }

    /**
     * Derives a slug from `name` for every existing row, then guarantees uniqueness by
     * suffixing collisions with the row id. Mirrors Gedmo's own output closely enough:
     * lowercase, Latin-1 accents folded, everything else collapsed into a single dash.
     */
    private function backfillSlugs(): void
    {
        $this->addSql(<<<'SQL'
            CREATE FUNCTION pg_temp.kbr_slugify(value text) RETURNS text AS $fn$
                SELECT trim(both '-' from regexp_replace(
                    lower(translate(
                        value,
                        'àáâãäåÀÁÂÃÄÅçÇèéêëÈÉÊËìíîïÌÍÎÏñÑòóôõöÒÓÔÕÖùúûüÙÚÛÜýÿÝ',
                        'aaaaaaAAAAAAcCeeeeEEEEiiiiIIIInNoooooOOOOOuuuuUUUUyyY'
                    )),
                    '[^a-z0-9]+', '-', 'g'
                ));
            $fn$ LANGUAGE sql IMMUTABLE;
            SQL);

        $backfill = <<<'SQL'
            DO $$
            DECLARE
                target text;
                duplicates bigint;
            BEGIN
                FOREACH target IN ARRAY ARRAY[{TABLES}] LOOP
                    EXECUTE format('UPDATE %I SET slug = pg_temp.kbr_slugify(name)', target);

                    -- Names made only of punctuation slugify to an empty string.
                    EXECUTE format(
                        $q$UPDATE %I SET slug = %L || '-' || id WHERE slug IS NULL OR slug = ''$q$,
                        target, target
                    );

                    EXECUTE format(
                        $q$UPDATE %I t SET slug = t.slug || '-' || t.id
                           WHERE t.slug IN (SELECT slug FROM %I GROUP BY slug HAVING count(*) > 1)$q$,
                        target, target
                    );

                    EXECUTE format(
                        $q$SELECT count(*) FROM (SELECT slug FROM %I GROUP BY slug HAVING count(*) > 1) d$q$,
                        target
                    ) INTO duplicates;

                    IF duplicates > 0 THEN
                        RAISE EXCEPTION 'could not derive unique slugs for table %; resolve the conflicting names by hand', target;
                    END IF;
                END LOOP;
            END $$;
            SQL;

        $tables = implode(', ', array_map(
            static fn (string $table): string => sprintf("'%s'", $table),
            self::SLUGGED_TABLES,
        ));

        $this->addSql(str_replace('{TABLES}', $tables, $backfill));
    }
}
