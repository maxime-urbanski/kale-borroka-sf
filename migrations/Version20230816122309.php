<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230816122309 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE images_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE images (id INT NOT NULL, image_file VARCHAR(255) NOT NULL, image_name VARCHAR(255) DEFAULT NULL, image_size INT DEFAULT NULL, created_at DATE DEFAULT NULL, updated_at DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN images.created_at IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN images.updated_at IS \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE album ADD images_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE album ADD CONSTRAINT FK_39986E43D44F05E5 FOREIGN KEY (images_id) REFERENCES images (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_39986E43D44F05E5 ON album (images_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_23A0E66989D9B62 ON article (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE album DROP CONSTRAINT FK_39986E43D44F05E5');
        $this->addSql('DROP SEQUENCE images_id_seq CASCADE');
        $this->addSql('DROP TABLE images');
        $this->addSql('DROP INDEX IDX_39986E43D44F05E5');
        $this->addSql('ALTER TABLE album DROP images_id');
        $this->addSql('DROP INDEX UNIQ_23A0E66989D9B62');
    }
}
