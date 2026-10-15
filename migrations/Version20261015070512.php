<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * The strip above the navbar and the footer's text in « Blocs de l'accueil », and the
 * footer's columns of links (« Colonnes du footer »). An existing homepage row gets the
 * current texts; the « À propos » and « Shop » columns are rebuilt from the pages placed
 * there, « Shop » listing every catalogue section (read live: prod imports its sections
 * after the migrations), which leaves pages only the bottom line as placement.
 */
final class Version20261015070512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Header strip, footer text and footer columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE footer_column_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE footer_link_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE footer_column (id INT NOT NULL, name VARCHAR(60) NOT NULL, position INT DEFAULT 0 NOT NULL, supports_listed BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE footer_link (id INT NOT NULL, name VARCHAR(60) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, position INT DEFAULT 0 NOT NULL, column_id INT NOT NULL, page_id INT DEFAULT NULL, support_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_6A05B20ABE8E8ED5 ON footer_link (column_id)');
        $this->addSql('CREATE INDEX IDX_6A05B20AC4663E4 ON footer_link (page_id)');
        $this->addSql('CREATE INDEX IDX_6A05B20A315B405 ON footer_link (support_id)');
        $this->addSql('ALTER TABLE footer_link ADD CONSTRAINT FK_6A05B20ABE8E8ED5 FOREIGN KEY (column_id) REFERENCES footer_column (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE footer_link ADD CONSTRAINT FK_6A05B20AC4663E4 FOREIGN KEY (page_id) REFERENCES page (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE footer_link ADD CONSTRAINT FK_6A05B20A315B405 FOREIGN KEY (support_id) REFERENCES support (id) ON DELETE CASCADE NOT DEFERRABLE');

        $this->addSql('ALTER TABLE homepage ADD strip_shown BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE homepage ADD strip_left VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage ADD strip_right VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage ADD footer_text TEXT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE homepage SET
                strip_left = 'Label & distro antifasciste depuis 2019',
                strip_right = 'Punk · antifa · DIY',
                footer_text = 'Label et distro indépendants. Punk, hardcore, oi! et tout ce qui fait du bruit contre l''extrême droite. Fait main, sans patron.'
            SQL);

        // « À propos »: its pages, in their footer order.
        $this->addSql("INSERT INTO footer_column (id, name, position) VALUES (nextval('footer_column_id_seq'), 'À propos', 1)");
        $this->addSql(<<<'SQL'
            INSERT INTO footer_link (id, column_id, page_id, position)
            SELECT nextval('footer_link_id_seq'), currval('footer_column_id_seq'), id, ROW_NUMBER() OVER (ORDER BY footer_position, title)
            FROM page WHERE footer_placement = 'about'
            SQL);
        // « Shop »: every catalogue section, then its pages.
        $this->addSql("INSERT INTO footer_column (id, name, position, supports_listed) VALUES (nextval('footer_column_id_seq'), 'Shop', 2, true)");
        $this->addSql(<<<'SQL'
            INSERT INTO footer_link (id, column_id, page_id, position)
            SELECT nextval('footer_link_id_seq'), currval('footer_column_id_seq'), id, ROW_NUMBER() OVER (ORDER BY footer_position, title)
            FROM page WHERE footer_placement = 'shop'
            SQL);
        $this->addSql("UPDATE page SET footer_placement = 'none' WHERE footer_placement IN ('about', 'shop')");
    }

    public function down(Schema $schema): void
    {
        // Pages linked from a column go back to « Shop » when it lists the sections, else to « À propos ».
        $this->addSql(<<<'SQL'
            UPDATE page SET footer_placement = CASE WHEN EXISTS (
                SELECT 1 FROM footer_link l JOIN footer_column c ON c.id = l.column_id WHERE l.page_id = page.id AND c.supports_listed
            ) THEN 'shop' ELSE 'about' END
            WHERE footer_placement = 'none' AND id IN (SELECT page_id FROM footer_link)
            SQL);
        $this->addSql('DROP TABLE footer_link');
        $this->addSql('DROP TABLE footer_column');
        $this->addSql('DROP SEQUENCE footer_link_id_seq');
        $this->addSql('DROP SEQUENCE footer_column_id_seq');
        $this->addSql('ALTER TABLE homepage DROP strip_shown');
        $this->addSql('ALTER TABLE homepage DROP strip_left');
        $this->addSql('ALTER TABLE homepage DROP strip_right');
        $this->addSql('ALTER TABLE homepage DROP footer_text');
    }
}
