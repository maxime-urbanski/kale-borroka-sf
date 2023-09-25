<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230925081236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE image_album (image_id INT NOT NULL, album_id INT NOT NULL, PRIMARY KEY(image_id, album_id))');
        $this->addSql('CREATE INDEX IDX_2EDDEAEA3DA5256D ON image_album (image_id)');
        $this->addSql('CREATE INDEX IDX_2EDDEAEA1137ABCF ON image_album (album_id)');
        $this->addSql('ALTER TABLE image_album ADD CONSTRAINT FK_2EDDEAEA3DA5256D FOREIGN KEY (image_id) REFERENCES image (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE image_album ADD CONSTRAINT FK_2EDDEAEA1137ABCF FOREIGN KEY (album_id) REFERENCES album (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE album DROP created_at');
        $this->addSql('ALTER TABLE album DROP updated_at');
        $this->addSql('ALTER TABLE image DROP image_file');
        $this->addSql('ALTER TABLE image DROP created_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE image_album DROP CONSTRAINT FK_2EDDEAEA3DA5256D');
        $this->addSql('ALTER TABLE image_album DROP CONSTRAINT FK_2EDDEAEA1137ABCF');
        $this->addSql('DROP TABLE image_album');
        $this->addSql('ALTER TABLE album ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE album ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN album.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN album.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE image ADD image_file VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE image ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN image.created_at IS \'(DC2Type:datetime_immutable)\'');
    }
}
