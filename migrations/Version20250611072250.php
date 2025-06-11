<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250611072250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_6424f4e87294869c');
        $this->addSql('ALTER TABLE wishlist_item DROP CONSTRAINT wishlist_item_pkey');
        $this->addSql('ALTER TABLE wishlist_item ALTER wishlist_id DROP NOT NULL');
        $this->addSql('CREATE INDEX IDX_6424F4E8FB8E54CD ON wishlist_item (wishlist_id)');
        $this->addSql('ALTER TABLE wishlist_item ADD PRIMARY KEY (article_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX IDX_6424F4E8FB8E54CD');
        $this->addSql('DROP INDEX wishlist_item_pkey');
        $this->addSql('ALTER TABLE wishlist_item ALTER wishlist_id SET NOT NULL');
        $this->addSql('CREATE INDEX idx_6424f4e87294869c ON wishlist_item (article_id)');
        $this->addSql('ALTER TABLE wishlist_item ADD PRIMARY KEY (wishlist_id)');
    }
}
