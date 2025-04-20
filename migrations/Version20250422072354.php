<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250422072354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE media_object_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE social_network_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE media_object (id INT NOT NULL, filename VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE social_network (id INT NOT NULL, file_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, is_publish BOOLEAN NOT NULL, in_footer BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EFFF522193CB796C ON social_network (file_id)');
        $this->addSql('ALTER TABLE social_network ADD CONSTRAINT FK_EFFF522193CB796C FOREIGN KEY (file_id) REFERENCES media_object (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE media_object_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE social_network_id_seq CASCADE');
        $this->addSql('ALTER TABLE social_network DROP CONSTRAINT FK_EFFF522193CB796C');
        $this->addSql('DROP TABLE media_object');
        $this->addSql('DROP TABLE social_network');
    }
}
