<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250608094535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE wishlist_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE wishlist (id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9CE12A31A76ED395 ON wishlist (user_id)');
        $this->addSql('CREATE TABLE wishlist_item (wishlist_id INT NOT NULL, article_id INT NOT NULL, added_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(wishlist_id))');
        $this->addSql('CREATE INDEX IDX_6424F4E87294869C ON wishlist_item (article_id)');
        $this->addSql('COMMENT ON COLUMN wishlist_item.added_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE wishlist ADD CONSTRAINT FK_9CE12A31A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT FK_6424F4E87294869C FOREIGN KEY (article_id) REFERENCES article (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT FK_6424F4E8FB8E54CD FOREIGN KEY (wishlist_id) REFERENCES wishlist (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE wishlist_id_seq CASCADE');
        $this->addSql('ALTER TABLE wishlist DROP CONSTRAINT FK_9CE12A31A76ED395');
        $this->addSql('ALTER TABLE wishlist_item DROP CONSTRAINT FK_6424F4E87294869C');
        $this->addSql('ALTER TABLE wishlist_item DROP CONSTRAINT FK_6424F4E8FB8E54CD');
        $this->addSql('DROP TABLE wishlist');
        $this->addSql('DROP TABLE wishlist_item');
    }
}
