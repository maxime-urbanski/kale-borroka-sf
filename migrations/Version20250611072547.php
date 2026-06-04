<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250611072547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_collection_items DROP CONSTRAINT user_collection_items_pkey');
        $this->addSql('CREATE INDEX IDX_DBF313C3514956FD ON user_collection_items (collection_id)');
        $this->addSql('ALTER TABLE user_collection_items ADD PRIMARY KEY (article_id, collection_id)');
        $this->addSql('ALTER TABLE wishlist_item DROP CONSTRAINT wishlist_item_pkey');
        $this->addSql('ALTER TABLE wishlist_item ALTER wishlist_id SET NOT NULL');
        $this->addSql('CREATE INDEX IDX_6424F4E87294869C ON wishlist_item (article_id)');
        $this->addSql('ALTER TABLE wishlist_item ADD PRIMARY KEY (article_id, wishlist_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX IDX_DBF313C3514956FD');
        $this->addSql('DROP INDEX user_collection_items_pkey');
        $this->addSql('ALTER TABLE user_collection_items ADD PRIMARY KEY (collection_id)');
        $this->addSql('DROP INDEX IDX_6424F4E87294869C');
        $this->addSql('DROP INDEX wishlist_item_pkey');
        $this->addSql('ALTER TABLE wishlist_item ALTER wishlist_id DROP NOT NULL');
        $this->addSql('ALTER TABLE wishlist_item ADD PRIMARY KEY (article_id)');
    }
}
