<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Introduce Edition (schema.org MusicRelease) between Album and Article, and reduce
 * Article to a pure Offer.
 *
 * Until now the only variant axis was the support, baked into the article row itself, so
 * a red and a black pressing of the same LP could only be told apart by their free-text
 * name. Each existing (album, support) pair becomes one Edition; every article is
 * reattached to it and loses its own name, slug, album and support.
 */
final class Version20260812150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the edition table between album and article, and turn article into an offer';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE edition_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE edition (id INT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, color VARCHAR(255) DEFAULT NULL, edition_label VARCHAR(255) DEFAULT NULL, catalog_number VARCHAR(255) DEFAULT NULL, pressing_run INT DEFAULT NULL, release_date DATE DEFAULT NULL, description TEXT DEFAULT NULL, album_id INT NOT NULL, support_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A891181F989D9B62 ON edition (slug)');
        $this->addSql('CREATE INDEX IDX_A891181F1137ABCF ON edition (album_id)');
        $this->addSql('CREATE INDEX IDX_A891181F315B405 ON edition (support_id)');
        $this->addSql('ALTER TABLE edition ADD CONSTRAINT FK_A891181F1137ABCF FOREIGN KEY (album_id) REFERENCES album (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE edition ADD CONSTRAINT FK_A891181F315B405 FOREIGN KEY (support_id) REFERENCES support (id) NOT DEFERRABLE');

        $this->addSql('CREATE TABLE image_edition (image_id INT NOT NULL, edition_id INT NOT NULL, PRIMARY KEY (image_id, edition_id))');
        $this->addSql('CREATE INDEX IDX_3D814FC33DA5256D ON image_edition (image_id)');
        $this->addSql('CREATE INDEX IDX_3D814FC374281A5E ON image_edition (edition_id)');
        $this->addSql('ALTER TABLE image_edition ADD CONSTRAINT FK_3D814FC33DA5256D FOREIGN KEY (image_id) REFERENCES image (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE image_edition ADD CONSTRAINT FK_3D814FC374281A5E FOREIGN KEY (edition_id) REFERENCES edition (id) ON DELETE CASCADE');

        // One edition per (album, support) pair currently in use. The name is the support
        // label — "LP", "CD" — which is exactly what the picker shows when an album has
        // no finer-grained variants yet; the slug is prefixed with the album slug, as
        // Gedmo's RelativeSlugHandler will do for every edition created from now on.
        $this->addSql(<<<'SQL'
            INSERT INTO edition (id, album_id, support_id, name, slug)
            SELECT nextval('edition_id_seq'), pair.album_id, pair.support_id,
                   upper(support.name), album.slug || '-' || support.name
            FROM (SELECT DISTINCT album_id, support_id FROM article) pair
            JOIN album ON album.id = pair.album_id
            JOIN support ON support.id = pair.support_id
            SQL);

        $this->addSql('ALTER TABLE article ADD edition_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE article
            SET edition_id = edition.id
            FROM edition
            WHERE edition.album_id = article.album_id
              AND edition.support_id = article.support_id
            SQL);

        $this->addSql(<<<'SQL'
            DO $$
            BEGIN
                IF EXISTS (SELECT 1 FROM article WHERE edition_id IS NULL) THEN
                    RAISE EXCEPTION 'some articles could not be attached to an edition; aborting';
                END IF;
            END $$;
            SQL);

        $this->addSql('ALTER TABLE article ALTER edition_id SET NOT NULL');

        $this->addSql('ALTER TABLE article ADD sku VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD gtin13 VARCHAR(13) DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD description TEXT DEFAULT NULL');
        $this->addSql("ALTER TABLE article ADD item_condition VARCHAR(32) DEFAULT 'new' NOT NULL");
        $this->addSql("ALTER TABLE article ADD availability VARCHAR(32) DEFAULT 'in_stock' NOT NULL");
        $this->addSql('ALTER TABLE article ADD available_from DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD weight INT DEFAULT NULL');

        // Existing rows only ever encoded availability through the stock count.
        $this->addSql("UPDATE article SET availability = 'out_of_stock' WHERE quantity <= 0");

        $this->addSql('ALTER TABLE article DROP CONSTRAINT fk_23a0e661137abcf');
        $this->addSql('ALTER TABLE article DROP CONSTRAINT fk_23a0e66315b405');
        $this->addSql('DROP INDEX uniq_23a0e66989d9b62');
        $this->addSql('DROP INDEX idx_23a0e66315b405');
        $this->addSql('DROP INDEX idx_23a0e661137abcf');
        $this->addSql('ALTER TABLE article DROP support_id');
        $this->addSql('ALTER TABLE article DROP album_id');
        $this->addSql('ALTER TABLE article DROP name');
        $this->addSql('ALTER TABLE article DROP slug');

        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E6674281A5E FOREIGN KEY (edition_id) REFERENCES edition (id) NOT DEFERRABLE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_23A0E66F9038C4 ON article (sku)');
        $this->addSql('CREATE INDEX IDX_23A0E6674281A5E ON article (edition_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article ADD support_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD album_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD slug VARCHAR(255) DEFAULT NULL');

        // Rebuild what the edition was carrying. Slugs are regenerated from the album
        // slug plus the article id: the original article slugs are gone for good.
        $this->addSql(<<<'SQL'
            UPDATE article
            SET support_id = edition.support_id,
                album_id = edition.album_id,
                name = album.name,
                slug = album.slug || '-' || article.id
            FROM edition
            JOIN album ON album.id = edition.album_id
            WHERE edition.id = article.edition_id
            SQL);

        $this->addSql('ALTER TABLE article ALTER support_id SET NOT NULL');
        $this->addSql('ALTER TABLE article ALTER album_id SET NOT NULL');
        $this->addSql('ALTER TABLE article ALTER name SET NOT NULL');
        $this->addSql('ALTER TABLE article ALTER slug SET NOT NULL');

        $this->addSql('DROP INDEX IDX_23A0E6674281A5E');
        $this->addSql('DROP INDEX UNIQ_23A0E66F9038C4');
        $this->addSql('ALTER TABLE article DROP CONSTRAINT FK_23A0E6674281A5E');
        $this->addSql('ALTER TABLE article DROP edition_id');
        $this->addSql('ALTER TABLE article DROP sku');
        $this->addSql('ALTER TABLE article DROP gtin13');
        $this->addSql('ALTER TABLE article DROP description');
        $this->addSql('ALTER TABLE article DROP item_condition');
        $this->addSql('ALTER TABLE article DROP availability');
        $this->addSql('ALTER TABLE article DROP available_from');
        $this->addSql('ALTER TABLE article DROP weight');

        $this->addSql('CREATE UNIQUE INDEX uniq_23a0e66989d9b62 ON article (slug)');
        $this->addSql('CREATE INDEX idx_23a0e66315b405 ON article (support_id)');
        $this->addSql('CREATE INDEX idx_23a0e661137abcf ON article (album_id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT fk_23a0e661137abcf FOREIGN KEY (album_id) REFERENCES album (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT fk_23a0e66315b405 FOREIGN KEY (support_id) REFERENCES support (id) NOT DEFERRABLE');

        $this->addSql('ALTER TABLE image_edition DROP CONSTRAINT FK_3D814FC33DA5256D');
        $this->addSql('ALTER TABLE image_edition DROP CONSTRAINT FK_3D814FC374281A5E');
        $this->addSql('DROP TABLE image_edition');
        $this->addSql('ALTER TABLE edition DROP CONSTRAINT FK_A891181F1137ABCF');
        $this->addSql('ALTER TABLE edition DROP CONSTRAINT FK_A891181F315B405');
        $this->addSql('DROP TABLE edition');
        $this->addSql('DROP SEQUENCE edition_id_seq');
    }
}
