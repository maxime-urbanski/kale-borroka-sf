<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Album picture order (the first one is the cover), and media library fixes: media files
 * get an update date so that Vich notices a replaced file, and `filename` goes back to the
 * bare stored name — it used to hold the public URI, rewritten on creation only.
 *
 * Artist links become a plain list of URLs (they were a site => URL object, never editable).
 */
final class Version20260929112340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Image position, media object updated_at and bare filenames, artist links as a list';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE image ADD position INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE media_object ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql("UPDATE media_object SET filename = regexp_replace(filename, '^/?media/', '')");
        $this->addSql(<<<'SQL'
            UPDATE artist SET links = COALESCE((SELECT json_agg(value) FROM json_each_text(links)), '[]'::json)
            WHERE json_typeof(links) = 'object'
            SQL);
        $this->addSql("ALTER TABLE artist ALTER links SET DEFAULT '[]'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE artist ALTER links SET DEFAULT '{}'");
        $this->addSql("UPDATE media_object SET filename = '/media/' || filename WHERE filename NOT LIKE '/media/%'");
        $this->addSql('ALTER TABLE image DROP position');
        $this->addSql('ALTER TABLE media_object DROP updated_at');
    }
}
