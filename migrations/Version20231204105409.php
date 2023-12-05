<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231204105409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT fk_8d93d6499ec2957b');
        $this->addSql('DROP INDEX uniq_8d93d6499ec2957b');
        $this->addSql('ALTER TABLE "user" DROP wantlist_id');
        $this->addSql('ALTER TABLE wantlist ADD user_wantlist_id INT NOT NULL');
        $this->addSql('ALTER TABLE wantlist ADD CONSTRAINT FK_B5560F9037D95CEE FOREIGN KEY (user_wantlist_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B5560F9037D95CEE ON wantlist (user_wantlist_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE wantlist DROP CONSTRAINT FK_B5560F9037D95CEE');
        $this->addSql('DROP INDEX UNIQ_B5560F9037D95CEE');
        $this->addSql('ALTER TABLE wantlist DROP user_wantlist_id');
        $this->addSql('ALTER TABLE "user" ADD wantlist_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT fk_8d93d6499ec2957b FOREIGN KEY (wantlist_id) REFERENCES wantlist (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_8d93d6499ec2957b ON "user" (wantlist_id)');
    }
}
