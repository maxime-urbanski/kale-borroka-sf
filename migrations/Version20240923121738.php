<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240923121738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE address_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE album_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE article_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE artist_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "group_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE image_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE label_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "order_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE order_details_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE payment_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE reset_password_request_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE song_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE style_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE support_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE transporter_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE user_collection_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE wantlist_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE address (id INT NOT NULL, users_id INT NOT NULL, name VARCHAR(100) NOT NULL, address VARCHAR(255) NOT NULL, complement_address VARCHAR(255) DEFAULT NULL, city VARCHAR(100) NOT NULL, zipcode VARCHAR(15) NOT NULL, country VARCHAR(100) NOT NULL, is_main_address BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D4E6F8167B3B43D ON address (users_id)');
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
        $this->addSql('CREATE TABLE article (id INT NOT NULL, support_id INT NOT NULL, album_id INT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, quantity INT NOT NULL, price INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_23A0E66989D9B62 ON article (slug)');
        $this->addSql('CREATE INDEX IDX_23A0E66315B405 ON article (support_id)');
        $this->addSql('CREATE INDEX IDX_23A0E661137ABCF ON article (album_id)');
        $this->addSql('COMMENT ON COLUMN article.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN article.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE artist (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "group" (id INT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE group_user (group_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(group_id, user_id))');
        $this->addSql('CREATE INDEX IDX_A4C98D39FE54D947 ON group_user (group_id)');
        $this->addSql('CREATE INDEX IDX_A4C98D39A76ED395 ON group_user (user_id)');
        $this->addSql('CREATE TABLE image (id INT NOT NULL, image_name VARCHAR(255) DEFAULT NULL, image_size INT DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN image.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE image_album (image_id INT NOT NULL, album_id INT NOT NULL, PRIMARY KEY(image_id, album_id))');
        $this->addSql('CREATE INDEX IDX_2EDDEAEA3DA5256D ON image_album (image_id)');
        $this->addSql('CREATE INDEX IDX_2EDDEAEA1137ABCF ON image_album (album_id)');
        $this->addSql('CREATE TABLE label (id INT NOT NULL, name VARCHAR(255) NOT NULL, is_friend BOOLEAN DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "order" (id INT NOT NULL, buyer_id INT DEFAULT NULL, address_id INT DEFAULT NULL, delivery_id INT DEFAULT NULL, payment_id INT DEFAULT NULL, reference VARCHAR(100) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(20) NOT NULL, total_price INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F52993986C755722 ON "order" (buyer_id)');
        $this->addSql('CREATE INDEX IDX_F5299398F5B7AF75 ON "order" (address_id)');
        $this->addSql('CREATE INDEX IDX_F529939812136921 ON "order" (delivery_id)');
        $this->addSql('CREATE INDEX IDX_F52993984C3A3BB ON "order" (payment_id)');
        $this->addSql('COMMENT ON COLUMN "order".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE order_details (id INT NOT NULL, product_id INT NOT NULL, orders_id INT NOT NULL, price INT NOT NULL, quantity INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_845CA2C14584665A ON order_details (product_id)');
        $this->addSql('CREATE INDEX IDX_845CA2C1CFFE9AD6 ON order_details (orders_id)');
        $this->addSql('CREATE TABLE payment (id INT NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE reset_password_request (id INT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7CE748AA76ED395 ON reset_password_request (user_id)');
        $this->addSql('COMMENT ON COLUMN reset_password_request.requested_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN reset_password_request.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE song (id INT NOT NULL, name VARCHAR(255) NOT NULL, track INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE song_artist (song_id INT NOT NULL, artist_id INT NOT NULL, PRIMARY KEY(song_id, artist_id))');
        $this->addSql('CREATE INDEX IDX_722870DA0BDB2F3 ON song_artist (song_id)');
        $this->addSql('CREATE INDEX IDX_722870DB7970CF8 ON song_artist (artist_id)');
        $this->addSql('CREATE TABLE style (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE support (id INT NOT NULL, name VARCHAR(255) NOT NULL, icon VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE transporter (id INT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT NOT NULL, price INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, default_address_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, lastname VARCHAR(100) NOT NULL, firstname VARCHAR(100) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649BD94FB16 ON "user" (default_address_id)');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE user_collection (id INT NOT NULL, user_collection_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5B2AA3DEBFC7FBAD ON user_collection (user_collection_id)');
        $this->addSql('CREATE TABLE user_collection_article (user_collection_id INT NOT NULL, article_id INT NOT NULL, PRIMARY KEY(user_collection_id, article_id))');
        $this->addSql('CREATE INDEX IDX_FA529A4CBFC7FBAD ON user_collection_article (user_collection_id)');
        $this->addSql('CREATE INDEX IDX_FA529A4C7294869C ON user_collection_article (article_id)');
        $this->addSql('CREATE TABLE user_collection_items (user_collection_id INT NOT NULL, article_id INT NOT NULL, since TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(user_collection_id, article_id))');
        $this->addSql('CREATE INDEX IDX_DBF313C3BFC7FBAD ON user_collection_items (user_collection_id)');
        $this->addSql('CREATE INDEX IDX_DBF313C37294869C ON user_collection_items (article_id)');
        $this->addSql('CREATE TABLE wantlist (id INT NOT NULL, user_wantlist_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B5560F9037D95CEE ON wantlist (user_wantlist_id)');
        $this->addSql('CREATE TABLE wantlist_article (wantlist_id INT NOT NULL, article_id INT NOT NULL, PRIMARY KEY(wantlist_id, article_id))');
        $this->addSql('CREATE INDEX IDX_AD4A478D9EC2957B ON wantlist_article (wantlist_id)');
        $this->addSql('CREATE INDEX IDX_AD4A478D7294869C ON wantlist_article (article_id)');
        $this->addSql('CREATE TABLE wantlist_items (wantlist_id INT NOT NULL, article_id INT NOT NULL, since TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(wantlist_id, article_id))');
        $this->addSql('CREATE INDEX IDX_D9E3E8C69EC2957B ON wantlist_items (wantlist_id)');
        $this->addSql('CREATE INDEX IDX_D9E3E8C67294869C ON wantlist_items (article_id)');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F8167B3B43D FOREIGN KEY (users_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE album ADD CONSTRAINT FK_39986E43B7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE album_label ADD CONSTRAINT FK_781F1ACE1137ABCF FOREIGN KEY (album_id) REFERENCES album (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE album_label ADD CONSTRAINT FK_781F1ACE33B92F39 FOREIGN KEY (label_id) REFERENCES label (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE album_song ADD CONSTRAINT FK_57E658E11137ABCF FOREIGN KEY (album_id) REFERENCES album (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE album_song ADD CONSTRAINT FK_57E658E1A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE album_style ADD CONSTRAINT FK_4505F24C1137ABCF FOREIGN KEY (album_id) REFERENCES album (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE album_style ADD CONSTRAINT FK_4505F24CBACD6074 FOREIGN KEY (style_id) REFERENCES style (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66315B405 FOREIGN KEY (support_id) REFERENCES support (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E661137ABCF FOREIGN KEY (album_id) REFERENCES album (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE group_user ADD CONSTRAINT FK_A4C98D39FE54D947 FOREIGN KEY (group_id) REFERENCES "group" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE group_user ADD CONSTRAINT FK_A4C98D39A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE image_album ADD CONSTRAINT FK_2EDDEAEA3DA5256D FOREIGN KEY (image_id) REFERENCES image (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE image_album ADD CONSTRAINT FK_2EDDEAEA1137ABCF FOREIGN KEY (album_id) REFERENCES album (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F52993986C755722 FOREIGN KEY (buyer_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F5299398F5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F529939812136921 FOREIGN KEY (delivery_id) REFERENCES transporter (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F52993984C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_details ADD CONSTRAINT FK_845CA2C14584665A FOREIGN KEY (product_id) REFERENCES article (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_details ADD CONSTRAINT FK_845CA2C1CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES "order" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE song_artist ADD CONSTRAINT FK_722870DA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE song_artist ADD CONSTRAINT FK_722870DB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D649BD94FB16 FOREIGN KEY (default_address_id) REFERENCES address (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_collection ADD CONSTRAINT FK_5B2AA3DEBFC7FBAD FOREIGN KEY (user_collection_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_collection_article ADD CONSTRAINT FK_FA529A4CBFC7FBAD FOREIGN KEY (user_collection_id) REFERENCES user_collection (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_collection_article ADD CONSTRAINT FK_FA529A4C7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_collection_items ADD CONSTRAINT FK_DBF313C3BFC7FBAD FOREIGN KEY (user_collection_id) REFERENCES user_collection (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_collection_items ADD CONSTRAINT FK_DBF313C37294869C FOREIGN KEY (article_id) REFERENCES article (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wantlist ADD CONSTRAINT FK_B5560F9037D95CEE FOREIGN KEY (user_wantlist_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wantlist_article ADD CONSTRAINT FK_AD4A478D9EC2957B FOREIGN KEY (wantlist_id) REFERENCES wantlist (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wantlist_article ADD CONSTRAINT FK_AD4A478D7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wantlist_items ADD CONSTRAINT FK_D9E3E8C69EC2957B FOREIGN KEY (wantlist_id) REFERENCES wantlist (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wantlist_items ADD CONSTRAINT FK_D9E3E8C67294869C FOREIGN KEY (article_id) REFERENCES article (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE address_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE album_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE article_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE artist_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "group_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE image_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE label_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "order_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE order_details_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE payment_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE reset_password_request_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE song_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE style_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE support_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE transporter_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE user_collection_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE wantlist_id_seq CASCADE');
        $this->addSql('ALTER TABLE address DROP CONSTRAINT FK_D4E6F8167B3B43D');
        $this->addSql('ALTER TABLE album DROP CONSTRAINT FK_39986E43B7970CF8');
        $this->addSql('ALTER TABLE album_label DROP CONSTRAINT FK_781F1ACE1137ABCF');
        $this->addSql('ALTER TABLE album_label DROP CONSTRAINT FK_781F1ACE33B92F39');
        $this->addSql('ALTER TABLE album_song DROP CONSTRAINT FK_57E658E11137ABCF');
        $this->addSql('ALTER TABLE album_song DROP CONSTRAINT FK_57E658E1A0BDB2F3');
        $this->addSql('ALTER TABLE album_style DROP CONSTRAINT FK_4505F24C1137ABCF');
        $this->addSql('ALTER TABLE album_style DROP CONSTRAINT FK_4505F24CBACD6074');
        $this->addSql('ALTER TABLE article DROP CONSTRAINT FK_23A0E66315B405');
        $this->addSql('ALTER TABLE article DROP CONSTRAINT FK_23A0E661137ABCF');
        $this->addSql('ALTER TABLE group_user DROP CONSTRAINT FK_A4C98D39FE54D947');
        $this->addSql('ALTER TABLE group_user DROP CONSTRAINT FK_A4C98D39A76ED395');
        $this->addSql('ALTER TABLE image_album DROP CONSTRAINT FK_2EDDEAEA3DA5256D');
        $this->addSql('ALTER TABLE image_album DROP CONSTRAINT FK_2EDDEAEA1137ABCF');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F52993986C755722');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F5299398F5B7AF75');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F529939812136921');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F52993984C3A3BB');
        $this->addSql('ALTER TABLE order_details DROP CONSTRAINT FK_845CA2C14584665A');
        $this->addSql('ALTER TABLE order_details DROP CONSTRAINT FK_845CA2C1CFFE9AD6');
        $this->addSql('ALTER TABLE reset_password_request DROP CONSTRAINT FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE song_artist DROP CONSTRAINT FK_722870DA0BDB2F3');
        $this->addSql('ALTER TABLE song_artist DROP CONSTRAINT FK_722870DB7970CF8');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649BD94FB16');
        $this->addSql('ALTER TABLE user_collection DROP CONSTRAINT FK_5B2AA3DEBFC7FBAD');
        $this->addSql('ALTER TABLE user_collection_article DROP CONSTRAINT FK_FA529A4CBFC7FBAD');
        $this->addSql('ALTER TABLE user_collection_article DROP CONSTRAINT FK_FA529A4C7294869C');
        $this->addSql('ALTER TABLE user_collection_items DROP CONSTRAINT FK_DBF313C3BFC7FBAD');
        $this->addSql('ALTER TABLE user_collection_items DROP CONSTRAINT FK_DBF313C37294869C');
        $this->addSql('ALTER TABLE wantlist DROP CONSTRAINT FK_B5560F9037D95CEE');
        $this->addSql('ALTER TABLE wantlist_article DROP CONSTRAINT FK_AD4A478D9EC2957B');
        $this->addSql('ALTER TABLE wantlist_article DROP CONSTRAINT FK_AD4A478D7294869C');
        $this->addSql('ALTER TABLE wantlist_items DROP CONSTRAINT FK_D9E3E8C69EC2957B');
        $this->addSql('ALTER TABLE wantlist_items DROP CONSTRAINT FK_D9E3E8C67294869C');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP TABLE album');
        $this->addSql('DROP TABLE album_label');
        $this->addSql('DROP TABLE album_song');
        $this->addSql('DROP TABLE album_style');
        $this->addSql('DROP TABLE article');
        $this->addSql('DROP TABLE artist');
        $this->addSql('DROP TABLE "group"');
        $this->addSql('DROP TABLE group_user');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE image_album');
        $this->addSql('DROP TABLE label');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('DROP TABLE order_details');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE song');
        $this->addSql('DROP TABLE song_artist');
        $this->addSql('DROP TABLE style');
        $this->addSql('DROP TABLE support');
        $this->addSql('DROP TABLE transporter');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE user_collection');
        $this->addSql('DROP TABLE user_collection_article');
        $this->addSql('DROP TABLE user_collection_items');
        $this->addSql('DROP TABLE wantlist');
        $this->addSql('DROP TABLE wantlist_article');
        $this->addSql('DROP TABLE wantlist_items');
    }
}
