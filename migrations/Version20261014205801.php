<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * The homepage's texts, edited from the back office (« Blocs de l'accueil »). No row: the
 * shop shows the defaults of Homepage until the first edit.
 */
final class Version20261014205801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Homepage texts: homepage';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE homepage_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE homepage (id INT NOT NULL, manifesto_shown BOOLEAN DEFAULT true NOT NULL, manifesto_kicker VARCHAR(60) DEFAULT NULL, manifesto_headline TEXT DEFAULT NULL, manifesto_highlight VARCHAR(120) DEFAULT NULL, manifesto_text TEXT DEFAULT NULL, manifesto_rule BOOLEAN DEFAULT true NOT NULL, manifesto_link_text VARCHAR(60) DEFAULT NULL, manifesto_url VARCHAR(255) DEFAULT NULL, manifesto_page_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_CB24D5EEAA282846 ON homepage (manifesto_page_id)');
        $this->addSql('ALTER TABLE homepage ADD CONSTRAINT FK_CB24D5EEAA282846 FOREIGN KEY (manifesto_page_id) REFERENCES page (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE homepage');
        $this->addSql('DROP SEQUENCE homepage_id_seq');
    }
}
