<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240924121451 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE invitation_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE invitation (id INT NOT NULL, guest_id INT NOT NULL, sent_by_id INT NOT NULL, community_id INT NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F11D61A29A4AA658 ON invitation (guest_id)');
        $this->addSql('CREATE INDEX IDX_F11D61A2A45BB98C ON invitation (sent_by_id)');
        $this->addSql('CREATE INDEX IDX_F11D61A2FDA7B0BF ON invitation (community_id)');
        $this->addSql('COMMENT ON COLUMN invitation.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A29A4AA658 FOREIGN KEY (guest_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A2A45BB98C FOREIGN KEY (sent_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A2FDA7B0BF FOREIGN KEY (community_id) REFERENCES "group" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE invitation_id_seq CASCADE');
        $this->addSql('ALTER TABLE invitation DROP CONSTRAINT FK_F11D61A29A4AA658');
        $this->addSql('ALTER TABLE invitation DROP CONSTRAINT FK_F11D61A2A45BB98C');
        $this->addSql('ALTER TABLE invitation DROP CONSTRAINT FK_F11D61A2FDA7B0BF');
        $this->addSql('DROP TABLE invitation');
    }
}
