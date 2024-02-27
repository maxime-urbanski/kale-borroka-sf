<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240227102827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE user_collection_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE user_collection (id INT NOT NULL, user_collection_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5B2AA3DEBFC7FBAD ON user_collection (user_collection_id)');
        $this->addSql('CREATE TABLE user_collection_article (user_collection_id INT NOT NULL, article_id INT NOT NULL, PRIMARY KEY(user_collection_id, article_id))');
        $this->addSql('CREATE INDEX IDX_FA529A4CBFC7FBAD ON user_collection_article (user_collection_id)');
        $this->addSql('CREATE INDEX IDX_FA529A4C7294869C ON user_collection_article (article_id)');
        $this->addSql('ALTER TABLE user_collection ADD CONSTRAINT FK_5B2AA3DEBFC7FBAD FOREIGN KEY (user_collection_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_collection_article ADD CONSTRAINT FK_FA529A4CBFC7FBAD FOREIGN KEY (user_collection_id) REFERENCES user_collection (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_collection_article ADD CONSTRAINT FK_FA529A4C7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE user_collection_id_seq CASCADE');
        $this->addSql('ALTER TABLE user_collection DROP CONSTRAINT FK_5B2AA3DEBFC7FBAD');
        $this->addSql('ALTER TABLE user_collection_article DROP CONSTRAINT FK_FA529A4CBFC7FBAD');
        $this->addSql('ALTER TABLE user_collection_article DROP CONSTRAINT FK_FA529A4C7294869C');
        $this->addSql('DROP TABLE user_collection');
        $this->addSql('DROP TABLE user_collection_article');
    }
}
