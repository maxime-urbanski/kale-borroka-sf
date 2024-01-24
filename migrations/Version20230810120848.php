<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230810120848 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article ADD support_id INT NOT NULL');
        $this->addSql('ALTER TABLE article ADD album_id INT NOT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66315B405 FOREIGN KEY (support_id) REFERENCES support (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E661137ABCF FOREIGN KEY (album_id) REFERENCES album (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_23A0E66315B405 ON article (support_id)');
        $this->addSql('CREATE INDEX IDX_23A0E661137ABCF ON article (album_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE article DROP CONSTRAINT FK_23A0E66315B405');
        $this->addSql('ALTER TABLE article DROP CONSTRAINT FK_23A0E661137ABCF');
        $this->addSql('DROP INDEX IDX_23A0E66315B405');
        $this->addSql('DROP INDEX IDX_23A0E661137ABCF');
        $this->addSql('ALTER TABLE article DROP support_id');
        $this->addSql('ALTER TABLE article DROP album_id');
    }
}
