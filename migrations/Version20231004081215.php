<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231004081215 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE address_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE address (id INT NOT NULL, users_id INT NOT NULL, name VARCHAR(100) NOT NULL, address VARCHAR(255) NOT NULL, complement_address VARCHAR(255), city VARCHAR(100) NOT NULL, zipcode VARCHAR(15) NOT NULL, country VARCHAR(100) NOT NULL, is_main_address BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D4E6F8167B3B43D ON address (users_id)');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F8167B3B43D FOREIGN KEY (users_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE address_id_seq CASCADE');
        $this->addSql('ALTER TABLE address DROP CONSTRAINT FK_D4E6F8167B3B43D');
        $this->addSql('DROP TABLE address');
    }
}
