<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250608112354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE wantlist_id_seq CASCADE');
        $this->addSql('ALTER TABLE wantlist_items DROP CONSTRAINT fk_d9e3e8c69ec2957b');
        $this->addSql('ALTER TABLE wantlist_items DROP CONSTRAINT fk_d9e3e8c67294869c');
        $this->addSql('ALTER TABLE wantlist_article DROP CONSTRAINT fk_ad4a478d9ec2957b');
        $this->addSql('ALTER TABLE wantlist_article DROP CONSTRAINT fk_ad4a478d7294869c');
        $this->addSql('ALTER TABLE wantlist DROP CONSTRAINT fk_b5560f9037d95cee');
        $this->addSql('DROP TABLE wantlist_items');
        $this->addSql('DROP TABLE wantlist_article');
        $this->addSql('DROP TABLE wantlist');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE wantlist_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE wantlist_items (wantlist_id INT NOT NULL, article_id INT NOT NULL, since TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(wantlist_id, article_id))');
        $this->addSql('CREATE INDEX idx_d9e3e8c67294869c ON wantlist_items (article_id)');
        $this->addSql('CREATE INDEX idx_d9e3e8c69ec2957b ON wantlist_items (wantlist_id)');
        $this->addSql('CREATE TABLE wantlist_article (wantlist_id INT NOT NULL, article_id INT NOT NULL, PRIMARY KEY(wantlist_id, article_id))');
        $this->addSql('CREATE INDEX idx_ad4a478d7294869c ON wantlist_article (article_id)');
        $this->addSql('CREATE INDEX idx_ad4a478d9ec2957b ON wantlist_article (wantlist_id)');
        $this->addSql('CREATE TABLE wantlist (id INT NOT NULL, user_wantlist_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_b5560f9037d95cee ON wantlist (user_wantlist_id)');
        $this->addSql('ALTER TABLE wantlist_items ADD CONSTRAINT fk_d9e3e8c69ec2957b FOREIGN KEY (wantlist_id) REFERENCES wantlist (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wantlist_items ADD CONSTRAINT fk_d9e3e8c67294869c FOREIGN KEY (article_id) REFERENCES article (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wantlist_article ADD CONSTRAINT fk_ad4a478d9ec2957b FOREIGN KEY (wantlist_id) REFERENCES wantlist (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wantlist_article ADD CONSTRAINT fk_ad4a478d7294869c FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wantlist ADD CONSTRAINT fk_b5560f9037d95cee FOREIGN KEY (user_wantlist_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
