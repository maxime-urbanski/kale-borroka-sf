<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240229102849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_collection_items (user_collection_id INT NOT NULL, article_id INT NOT NULL, since TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(user_collection_id, article_id))');
        $this->addSql('CREATE INDEX IDX_DBF313C3BFC7FBAD ON user_collection_items (user_collection_id)');
        $this->addSql('CREATE INDEX IDX_DBF313C37294869C ON user_collection_items (article_id)');
        $this->addSql('ALTER TABLE user_collection_items ADD CONSTRAINT FK_DBF313C3BFC7FBAD FOREIGN KEY (user_collection_id) REFERENCES user_collection (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_collection_items ADD CONSTRAINT FK_DBF313C37294869C FOREIGN KEY (article_id) REFERENCES article (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE user_collection_items DROP CONSTRAINT FK_DBF313C3BFC7FBAD');
        $this->addSql('ALTER TABLE user_collection_items DROP CONSTRAINT FK_DBF313C37294869C');
        $this->addSql('DROP TABLE user_collection_items');
    }
}
