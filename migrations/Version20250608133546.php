<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250608133546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_collection_article DROP CONSTRAINT fk_fa529a4cbfc7fbad');
        $this->addSql('ALTER TABLE user_collection_article DROP CONSTRAINT fk_fa529a4c7294869c');
        $this->addSql('DROP TABLE user_collection_article');
        $this->addSql('ALTER TABLE user_collection DROP CONSTRAINT fk_5b2aa3debfc7fbad');
        $this->addSql('DROP INDEX uniq_5b2aa3debfc7fbad');
        $this->addSql('ALTER TABLE user_collection RENAME COLUMN user_collection_id TO user_id');
        $this->addSql('ALTER TABLE user_collection ADD CONSTRAINT FK_5B2AA3DEA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5B2AA3DEA76ED395 ON user_collection (user_id)');
        $this->addSql('ALTER TABLE user_collection_items DROP CONSTRAINT fk_dbf313c3bfc7fbad');
        $this->addSql('DROP INDEX idx_dbf313c3bfc7fbad');
        $this->addSql('ALTER TABLE user_collection_items DROP CONSTRAINT user_collection_items_pkey');
        $this->addSql('ALTER TABLE user_collection_items ADD added_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE user_collection_items DROP since');
        $this->addSql('ALTER TABLE user_collection_items RENAME COLUMN user_collection_id TO collection_id');
        $this->addSql('COMMENT ON COLUMN user_collection_items.added_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_collection_items ADD CONSTRAINT FK_DBF313C3514956FD FOREIGN KEY (collection_id) REFERENCES user_collection (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_collection_items ADD PRIMARY KEY (collection_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE user_collection_article (user_collection_id INT NOT NULL, article_id INT NOT NULL, PRIMARY KEY(user_collection_id, article_id))');
        $this->addSql('CREATE INDEX idx_fa529a4c7294869c ON user_collection_article (article_id)');
        $this->addSql('CREATE INDEX idx_fa529a4cbfc7fbad ON user_collection_article (user_collection_id)');
        $this->addSql('ALTER TABLE user_collection_article ADD CONSTRAINT fk_fa529a4cbfc7fbad FOREIGN KEY (user_collection_id) REFERENCES user_collection (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_collection_article ADD CONSTRAINT fk_fa529a4c7294869c FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_collection_items DROP CONSTRAINT FK_DBF313C3514956FD');
        $this->addSql('DROP INDEX user_collection_items_pkey');
        $this->addSql('ALTER TABLE user_collection_items ADD since TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE user_collection_items DROP added_at');
        $this->addSql('ALTER TABLE user_collection_items RENAME COLUMN collection_id TO user_collection_id');
        $this->addSql('ALTER TABLE user_collection_items ADD CONSTRAINT fk_dbf313c3bfc7fbad FOREIGN KEY (user_collection_id) REFERENCES user_collection (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_dbf313c3bfc7fbad ON user_collection_items (user_collection_id)');
        $this->addSql('ALTER TABLE user_collection_items ADD PRIMARY KEY (user_collection_id, article_id)');
        $this->addSql('ALTER TABLE user_collection DROP CONSTRAINT FK_5B2AA3DEA76ED395');
        $this->addSql('DROP INDEX UNIQ_5B2AA3DEA76ED395');
        $this->addSql('ALTER TABLE user_collection RENAME COLUMN user_id TO user_collection_id');
        $this->addSql('ALTER TABLE user_collection ADD CONSTRAINT fk_5b2aa3debfc7fbad FOREIGN KEY (user_collection_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_5b2aa3debfc7fbad ON user_collection (user_collection_id)');
    }
}
