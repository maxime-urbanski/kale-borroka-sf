<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240229101145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE wantlist_items (wantlist_id INT NOT NULL, article_id INT NOT NULL, since TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(wantlist_id, article_id))');
        $this->addSql('CREATE INDEX IDX_D9E3E8C69EC2957B ON wantlist_items (wantlist_id)');
        $this->addSql('CREATE INDEX IDX_D9E3E8C67294869C ON wantlist_items (article_id)');
        $this->addSql('ALTER TABLE wantlist_items ADD CONSTRAINT FK_D9E3E8C69EC2957B FOREIGN KEY (wantlist_id) REFERENCES wantlist (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wantlist_items ADD CONSTRAINT FK_D9E3E8C67294869C FOREIGN KEY (article_id) REFERENCES article (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE wantlist_items DROP CONSTRAINT FK_D9E3E8C69EC2957B');
        $this->addSql('ALTER TABLE wantlist_items DROP CONSTRAINT FK_D9E3E8C67294869C');
        $this->addSql('DROP TABLE wantlist_items');
    }
}
