<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231011095235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE wantlist_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE wantlist (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE wantlist_article (wantlist_id INT NOT NULL, article_id INT NOT NULL, PRIMARY KEY(wantlist_id, article_id))');
        $this->addSql('CREATE INDEX IDX_AD4A478D9EC2957B ON wantlist_article (wantlist_id)');
        $this->addSql('CREATE INDEX IDX_AD4A478D7294869C ON wantlist_article (article_id)');
        $this->addSql('ALTER TABLE wantlist_article ADD CONSTRAINT FK_AD4A478D9EC2957B FOREIGN KEY (wantlist_id) REFERENCES wantlist (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wantlist_article ADD CONSTRAINT FK_AD4A478D7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD wantlist_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D6499EC2957B FOREIGN KEY (wantlist_id) REFERENCES wantlist (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6499EC2957B ON "user" (wantlist_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D6499EC2957B');
        $this->addSql('DROP SEQUENCE wantlist_id_seq CASCADE');
        $this->addSql('ALTER TABLE wantlist_article DROP CONSTRAINT FK_AD4A478D9EC2957B');
        $this->addSql('ALTER TABLE wantlist_article DROP CONSTRAINT FK_AD4A478D7294869C');
        $this->addSql('DROP TABLE wantlist');
        $this->addSql('DROP TABLE wantlist_article');
        $this->addSql('DROP INDEX UNIQ_8D93D6499EC2957B');
        $this->addSql('ALTER TABLE "user" DROP wantlist_id');
    }
}
