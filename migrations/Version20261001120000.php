<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Styles become the official list of Style::OFFICIAL. Existing styles are folded into it: same
 * name whatever the case and surrounding spaces, or a known alias (Oi! → Oi, Streetpunk → Street
 * Punk, Rap → Hip Hop…), duplicates included, their albums keeping the style. A style that maps
 * to none is dropped with its links, and listed in a warning first.
 */
final class Version20261001120000 extends AbstractMigration
{
    /** Copy of Style::OFFICIAL at the time of this migration. */
    private const array OFFICIAL = [
        'Punk', 'Oi', 'Street Punk', 'Hardcore', 'Crust', 'Anarcho-Punk', 'Celtic Punk',
        'Ska', 'Ska Punk', 'Two Tone', 'Rocksteady', 'Reggae', 'Dub',
        'Rock & Roll', 'Rockabilly', 'Psychobilly', 'Garage Rock',
        'Folk', 'Folk Punk', 'Chanson', 'Hip Hop',
    ];

    /** Lowercased former spelling => official name. */
    private const array ALIASES = [
        'oi!' => 'Oi',
        'streetpunk' => 'Street Punk',
        'street-punk' => 'Street Punk',
        'rap' => 'Hip Hop',
        'hip-hop' => 'Hip Hop',
        "rock'n'roll" => 'Rock & Roll',
        'rock n roll' => 'Rock & Roll',
        'rock and roll' => 'Rock & Roll',
        'ska-punk' => 'Ska Punk',
        '2 tone' => 'Two Tone',
        '2-tone' => 'Two Tone',
        'two-tone' => 'Two Tone',
        'anarcho punk' => 'Anarcho-Punk',
        'hardcore punk' => 'Hardcore',
    ];

    public function getDescription(): string
    {
        return 'Official list of styles, unique by name';
    }

    public function up(Schema $schema): void
    {
        $mapping = [];
        foreach (self::OFFICIAL as $name) {
            $mapping[mb_strtolower($name)] = $name;
        }
        $mapping += self::ALIASES;

        // Said before it happens: their albums lose these styles.
        $dropped = array_filter(
            $this->connection->fetchFirstColumn('SELECT DISTINCT name FROM style'),
            static fn (mixed $name): bool => !isset($mapping[mb_strtolower(trim((string) $name))]),
        );
        $this->warnIf([] !== $dropped, 'Styles outside the official list, dropped with their album links: '.implode(', ', $dropped));

        $this->addSql('CREATE TEMPORARY TABLE style_mapping (former VARCHAR(255) PRIMARY KEY, official VARCHAR(255) NOT NULL)');
        foreach ($mapping as $former => $official) {
            $this->addSql('INSERT INTO style_mapping (former, official) VALUES (?, ?)', [$former, $official]);
        }

        // One row per official style: the oldest one already spelt that way, or a new one.
        $this->addSql(<<<'SQL'
            INSERT INTO style (id, name)
            SELECT nextval('style_id_seq'), official
            FROM (SELECT DISTINCT official FROM style_mapping) o
            WHERE NOT EXISTS (SELECT 1 FROM style s WHERE s.name = o.official)
            SQL);
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE style_kept AS
            SELECT name, min(id) AS id FROM style
            WHERE name IN (SELECT official FROM style_mapping)
            GROUP BY name
            SQL);

        // Albums of every other row (alias, other case, duplicate) get the kept one, once.
        $this->addSql(<<<'SQL'
            INSERT INTO album_style (album_id, style_id)
            SELECT DISTINCT a.album_id, k.id
            FROM album_style a
            INNER JOIN style f ON f.id = a.style_id
            INNER JOIN style_mapping m ON m.former = lower(trim(f.name))
            INNER JOIN style_kept k ON k.name = m.official
            WHERE k.id <> f.id
            ON CONFLICT DO NOTHING
            SQL);
        // Their links go with them (ON DELETE CASCADE).
        $this->addSql('DELETE FROM style WHERE id NOT IN (SELECT id FROM style_kept)');
        $this->addSql('DROP TABLE style_kept');
        $this->addSql('DROP TABLE style_mapping');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_33BDB86A5E237E06 ON style (name)');
    }

    public function down(Schema $schema): void
    {
        $this->warnIf(true, 'Only the unique index is dropped: the folded and dropped styles are not restored.');
        $this->addSql('DROP INDEX UNIQ_33BDB86A5E237E06');
    }
}
