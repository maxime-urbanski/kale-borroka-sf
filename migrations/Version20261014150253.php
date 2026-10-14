<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * The record put forward at the top of the homepage (pre-order, favourite, to discover).
 */
final class Version20261014150253 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Homepage feature: featured_release';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE featured_release_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE featured_release (id INT NOT NULL, kind VARCHAR(20) NOT NULL, headline VARCHAR(120) DEFAULT NULL, description TEXT DEFAULT NULL, release_date DATE DEFAULT NULL, listen_url VARCHAR(255) DEFAULT NULL, published BOOLEAN DEFAULT false NOT NULL, date_published TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, expires TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, release_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_3F861350B12A727D ON featured_release (release_id)');
        $this->addSql('ALTER TABLE featured_release ADD CONSTRAINT FK_3F861350B12A727D FOREIGN KEY (release_id) REFERENCES article (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE featured_release');
        $this->addSql('DROP SEQUENCE featured_release_id_seq');
    }
}
