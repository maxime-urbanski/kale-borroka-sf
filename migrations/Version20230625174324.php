<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230625174324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE album_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE artist_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE label_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE shape_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE song_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE style_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE album (id INT NOT NULL, artist_id INT NOT NULL, name VARCHAR(255) NOT NULL, note TEXT DEFAULT NULL, kbr_production BOOLEAN NOT NULL, folder VARCHAR(255) DEFAULT NULL, date_release DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_39986E43B7970CF8 ON album (artist_id)');
        $this->addSql('CREATE TABLE album_label (album_id INT NOT NULL, label_id INT NOT NULL, PRIMARY KEY(album_id, label_id))');
        $this->addSql('CREATE INDEX IDX_781F1ACE1137ABCF ON album_label (album_id)');
        $this->addSql('CREATE INDEX IDX_781F1ACE33B92F39 ON album_label (label_id)');
        $this->addSql('CREATE TABLE album_song (album_id INT NOT NULL, song_id INT NOT NULL, PRIMARY KEY(album_id, song_id))');
        $this->addSql('CREATE INDEX IDX_57E658E11137ABCF ON album_song (album_id)');
        $this->addSql('CREATE INDEX IDX_57E658E1A0BDB2F3 ON album_song (song_id)');
        $this->addSql('CREATE TABLE album_style (album_id INT NOT NULL, style_id INT NOT NULL, PRIMARY KEY(album_id, style_id))');
        $this->addSql('CREATE INDEX IDX_4505F24C1137ABCF ON album_style (album_id)');
        $this->addSql('CREATE INDEX IDX_4505F24CBACD6074 ON album_style (style_id)');
        $this->addSql('CREATE TABLE artist (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE label (id INT NOT NULL, name VARCHAR(255) NOT NULL, is_friend BOOLEAN DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE shape (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE song (id INT NOT NULL, name VARCHAR(255) NOT NULL, track INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE style (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE album ADD CONSTRAINT FK_39986E43B7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE album_label ADD CONSTRAINT FK_781F1ACE1137ABCF FOREIGN KEY (album_id) REFERENCES album (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE album_label ADD CONSTRAINT FK_781F1ACE33B92F39 FOREIGN KEY (label_id) REFERENCES label (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE album_song ADD CONSTRAINT FK_57E658E11137ABCF FOREIGN KEY (album_id) REFERENCES album (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE album_song ADD CONSTRAINT FK_57E658E1A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE album_style ADD CONSTRAINT FK_4505F24C1137ABCF FOREIGN KEY (album_id) REFERENCES album (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE album_style ADD CONSTRAINT FK_4505F24CBACD6074 FOREIGN KEY (style_id) REFERENCES style (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE album_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE artist_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE label_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE shape_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE song_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE style_id_seq CASCADE');
        $this->addSql('ALTER TABLE album DROP CONSTRAINT FK_39986E43B7970CF8');
        $this->addSql('ALTER TABLE album_label DROP CONSTRAINT FK_781F1ACE1137ABCF');
        $this->addSql('ALTER TABLE album_label DROP CONSTRAINT FK_781F1ACE33B92F39');
        $this->addSql('ALTER TABLE album_song DROP CONSTRAINT FK_57E658E11137ABCF');
        $this->addSql('ALTER TABLE album_song DROP CONSTRAINT FK_57E658E1A0BDB2F3');
        $this->addSql('ALTER TABLE album_style DROP CONSTRAINT FK_4505F24C1137ABCF');
        $this->addSql('ALTER TABLE album_style DROP CONSTRAINT FK_4505F24CBACD6074');
        $this->addSql('DROP TABLE album');
        $this->addSql('DROP TABLE album_label');
        $this->addSql('DROP TABLE album_song');
        $this->addSql('DROP TABLE album_style');
        $this->addSql('DROP TABLE artist');
        $this->addSql('DROP TABLE label');
        $this->addSql('DROP TABLE shape');
        $this->addSql('DROP TABLE song');
        $this->addSql('DROP TABLE style');
    }
}
