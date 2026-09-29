<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Catalogue model: Article becomes a single-table-inheritance root (release / book / merch),
 * the physical format moves from Support to Release::$format, and Support gets its SupportType code.
 *
 * Existing rows are migrated: every article becomes a published release whose format comes
 * from its old support (lp → vinyl_12, ep → vinyl_7 with its album flagged EP, cd, tape →
 * cassette), except fanzines, which become books and lose their album link.
 */
final class Version20260929092205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Article STI (release/book/merch), formats, merch, categories, CMS pages, shop settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE category_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE merch_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE page_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE shop_settings_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE article_image (article_id INT NOT NULL, image_id INT NOT NULL, PRIMARY KEY (article_id, image_id))');
        $this->addSql('CREATE INDEX IDX_B28A764E7294869C ON article_image (article_id)');
        $this->addSql('CREATE INDEX IDX_B28A764E3DA5256D ON article_image (image_id)');
        $this->addSql('CREATE TABLE category (id INT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, parent_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C1989D9B62 ON category (slug)');
        $this->addSql('CREATE INDEX IDX_64C19C1727ACA70 ON category (parent_id)');
        $this->addSql('CREATE TABLE merch (id INT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, published BOOLEAN DEFAULT false NOT NULL, artist_id INT DEFAULT NULL, category_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F1B42EE0989D9B62 ON merch (slug)');
        $this->addSql('CREATE INDEX IDX_F1B42EE0B7970CF8 ON merch (artist_id)');
        $this->addSql('CREATE INDEX IDX_F1B42EE012469DE2 ON merch (category_id)');
        $this->addSql('CREATE TABLE merch_image (merch_id INT NOT NULL, image_id INT NOT NULL, PRIMARY KEY (merch_id, image_id))');
        $this->addSql('CREATE INDEX IDX_1A6A20E8A86BD8 ON merch_image (merch_id)');
        $this->addSql('CREATE INDEX IDX_1A6A20E3DA5256D ON merch_image (image_id)');
        $this->addSql('CREATE TABLE page (id INT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, content TEXT NOT NULL, published BOOLEAN DEFAULT false NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_140AB620989D9B62 ON page (slug)');
        $this->addSql('CREATE TABLE shop_settings (id INT NOT NULL, low_stock_threshold INT DEFAULT 2 NOT NULL, free_shipping_threshold INT DEFAULT NULL, contact_email VARCHAR(180) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE article_image ADD CONSTRAINT FK_B28A764E7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_image ADD CONSTRAINT FK_B28A764E3DA5256D FOREIGN KEY (image_id) REFERENCES image (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE merch ADD CONSTRAINT FK_F1B42EE0B7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id)');
        $this->addSql('ALTER TABLE merch ADD CONSTRAINT FK_F1B42EE012469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE merch_image ADD CONSTRAINT FK_1A6A20E8A86BD8 FOREIGN KEY (merch_id) REFERENCES merch (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE merch_image ADD CONSTRAINT FK_1A6A20E3DA5256D FOREIGN KEY (image_id) REFERENCES image (id) ON DELETE CASCADE');

        // Support: the code is the old name, which has always been the URL segment.
        $this->addSql('ALTER TABLE support ADD code VARCHAR(20) DEFAULT NULL');
        $this->addSql('UPDATE support SET code = lower(name)');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF EXISTS (SELECT 1 FROM support WHERE code NOT IN ('lp', 'ep', 'cd', 'fanzine', 'tape')) THEN
                    RAISE EXCEPTION 'support.name must be one of lp, ep, cd, fanzine, tape to derive support.code';
                END IF;
            END $$
            SQL);
        $this->addSql('ALTER TABLE support ALTER code SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8004EBA577153098 ON support (code)');

        // Album
        $this->addSql('ALTER TABLE album ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE album SET slug = trim(both '-' from lower(regexp_replace(name, '[^a-zA-Z0-9]+', '-', 'g'))) || '-' || id");
        $this->addSql('ALTER TABLE album ALTER slug SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_39986E43989D9B62 ON album (slug)');
        $this->addSql('ALTER TABLE album ADD release_type VARCHAR(20) DEFAULT \'album\' NOT NULL');
        $this->addSql(<<<'SQL'
            UPDATE album SET release_type = 'ep'
            WHERE id IN (SELECT a.album_id FROM article a INNER JOIN support s ON s.id = a.support_id WHERE s.code = 'ep')
            SQL);

        // Article: new columns, nullable first so existing rows can be filled in.
        $this->addSql('ALTER TABLE article ADD type VARCHAR(20) DEFAULT \'release\' NOT NULL');
        $this->addSql('ALTER TABLE article ALTER type DROP DEFAULT');
        $this->addSql('ALTER TABLE article ADD sku VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD gtin VARCHAR(14) DEFAULT NULL');
        $this->addSql('ALTER TABLE article RENAME COLUMN quantity TO stock');
        $this->addSql('ALTER TABLE article ADD color VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD item_condition VARCHAR(20) DEFAULT \'new\' NOT NULL');
        $this->addSql('ALTER TABLE article ADD published BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE article ADD format VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD edition_label VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD limited_to INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD pressing_year INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD catalog_number VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD label_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD book_type VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD author VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD isbn VARCHAR(17) DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD number_of_pages INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD publisher_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD size VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD merch_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ALTER album_id DROP NOT NULL');

        // Everything already in the shop was on sale: keep it published.
        $this->addSql("UPDATE article SET sku = 'KBR-' || lpad(id::text, 6, '0'), published = true");
        $this->addSql(<<<'SQL'
            UPDATE article a SET format = CASE s.code
                WHEN 'lp' THEN 'vinyl_12'
                WHEN 'ep' THEN 'vinyl_7'
                WHEN 'cd' THEN 'cd'
                WHEN 'tape' THEN 'cassette'
            END
            FROM support s WHERE s.id = a.support_id AND s.code <> 'fanzine'
            SQL);
        $this->addSql(<<<'SQL'
            UPDATE article a SET type = 'book', book_type = 'fanzine', album_id = NULL
            FROM support s WHERE s.id = a.support_id AND s.code = 'fanzine'
            SQL);
        $this->addSql('ALTER TABLE article ALTER sku SET NOT NULL');

        $this->addSql('ALTER TABLE article DROP CONSTRAINT fk_23a0e66315b405');
        $this->addSql('DROP INDEX idx_23a0e66315b405');
        $this->addSql('ALTER TABLE article DROP support_id');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E6633B92F39 FOREIGN KEY (label_id) REFERENCES label (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E6640C86FCE FOREIGN KEY (publisher_id) REFERENCES label (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E6612469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E668A86BD8 FOREIGN KEY (merch_id) REFERENCES merch (id) NOT DEFERRABLE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_23A0E66F9038C4 ON article (sku)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_23A0E66CA784C9B ON article (gtin)');
        $this->addSql('CREATE INDEX IDX_23A0E6633B92F39 ON article (label_id)');
        $this->addSql('CREATE INDEX IDX_23A0E6640C86FCE ON article (publisher_id)');
        $this->addSql('CREATE INDEX IDX_23A0E6612469DE2 ON article (category_id)');
        $this->addSql('CREATE INDEX IDX_23A0E668A86BD8 ON article (merch_id)');

        // Artist
        $this->addSql('ALTER TABLE artist ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE artist SET slug = trim(both '-' from lower(regexp_replace(name, '[^a-zA-Z0-9]+', '-', 'g'))) || '-' || id");
        $this->addSql('ALTER TABLE artist ALTER slug SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1599687989D9B62 ON artist (slug)');
        $this->addSql('ALTER TABLE artist ADD description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE artist ADD country VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE artist ADD links JSON DEFAULT \'{}\' NOT NULL');

        // Label: "friend" labels are the ones we distribute.
        $this->addSql('ALTER TABLE label ADD is_distro BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('UPDATE label SET is_distro = COALESCE(is_friend, false)');
        $this->addSql('ALTER TABLE label DROP is_friend');
        $this->addSql('ALTER TABLE label ADD website VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE song ADD duration INT DEFAULT NULL');
    }

    /**
     * Lossy by nature: merch variants are deleted, books go back to the fanzine support
     * without an album — which the old schema forbids, so they are deleted too.
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song DROP duration');

        $this->addSql('ALTER TABLE label ADD is_friend BOOLEAN DEFAULT NULL');
        $this->addSql('UPDATE label SET is_friend = is_distro');
        $this->addSql('ALTER TABLE label DROP is_distro');
        $this->addSql('ALTER TABLE label DROP website');

        $this->addSql('DROP INDEX UNIQ_1599687989D9B62');
        $this->addSql('ALTER TABLE artist DROP slug');
        $this->addSql('ALTER TABLE artist DROP description');
        $this->addSql('ALTER TABLE artist DROP country');
        $this->addSql('ALTER TABLE artist DROP links');

        $this->addSql("DELETE FROM article WHERE type <> 'release' OR album_id IS NULL");
        $this->addSql('ALTER TABLE article ADD support_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE article a SET support_id = s.id FROM support s, album al
            WHERE al.id = a.album_id AND s.code = CASE
                WHEN a.format = 'cd' THEN 'cd'
                WHEN a.format = 'cassette' THEN 'tape'
                WHEN a.format = 'vinyl_7' OR al.release_type = 'ep' THEN 'ep'
                ELSE 'lp'
            END
            SQL);
        $this->addSql('ALTER TABLE article ALTER support_id SET NOT NULL');
        $this->addSql('ALTER TABLE article ALTER album_id SET NOT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT fk_23a0e66315b405 FOREIGN KEY (support_id) REFERENCES support (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_23a0e66315b405 ON article (support_id)');
        $this->addSql('ALTER TABLE article RENAME COLUMN stock TO quantity');
        $this->addSql('ALTER TABLE article DROP CONSTRAINT FK_23A0E6633B92F39');
        $this->addSql('ALTER TABLE article DROP CONSTRAINT FK_23A0E6640C86FCE');
        $this->addSql('ALTER TABLE article DROP CONSTRAINT FK_23A0E6612469DE2');
        $this->addSql('ALTER TABLE article DROP CONSTRAINT FK_23A0E668A86BD8');
        foreach (['sku', 'gtin', 'color', 'item_condition', 'published', 'type', 'format', 'edition_label', 'limited_to',
            'pressing_year', 'catalog_number', 'label_id', 'book_type', 'author', 'isbn', 'number_of_pages',
            'publisher_id', 'category_id', 'size', 'merch_id'] as $column) {
            $this->addSql(\sprintf('ALTER TABLE article DROP %s', $column));
        }

        $this->addSql('DROP INDEX UNIQ_39986E43989D9B62');
        $this->addSql('ALTER TABLE album DROP slug');
        $this->addSql('ALTER TABLE album DROP release_type');

        $this->addSql('DROP INDEX UNIQ_8004EBA577153098');
        $this->addSql('ALTER TABLE support DROP code');

        $this->addSql('DROP SEQUENCE category_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE merch_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE page_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE shop_settings_id_seq CASCADE');
        $this->addSql('ALTER TABLE article_image DROP CONSTRAINT FK_B28A764E7294869C');
        $this->addSql('ALTER TABLE article_image DROP CONSTRAINT FK_B28A764E3DA5256D');
        $this->addSql('ALTER TABLE category DROP CONSTRAINT FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE merch DROP CONSTRAINT FK_F1B42EE0B7970CF8');
        $this->addSql('ALTER TABLE merch DROP CONSTRAINT FK_F1B42EE012469DE2');
        $this->addSql('ALTER TABLE merch_image DROP CONSTRAINT FK_1A6A20E8A86BD8');
        $this->addSql('ALTER TABLE merch_image DROP CONSTRAINT FK_1A6A20E3DA5256D');
        $this->addSql('DROP TABLE article_image');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE merch');
        $this->addSql('DROP TABLE merch_image');
        $this->addSql('DROP TABLE page');
        $this->addSql('DROP TABLE shop_settings');
    }
}
