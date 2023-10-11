<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231005131317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE "order_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE order_details_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE "order" (id INT NOT NULL, buyer_id INT DEFAULT NULL, address_id INT DEFAULT NULL, delivery_id INT DEFAULT NULL, reference VARCHAR(100) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(20) NOT NULL, total_price INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F52993986C755722 ON "order" (buyer_id)');
        $this->addSql('CREATE INDEX IDX_F5299398F5B7AF75 ON "order" (address_id)');
        $this->addSql('CREATE INDEX IDX_F529939812136921 ON "order" (delivery_id)');
        $this->addSql('COMMENT ON COLUMN "order".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE order_details (id INT NOT NULL, product_id INT NOT NULL, orders_id INT NOT NULL, price INT NOT NULL, quantity INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_845CA2C14584665A ON order_details (product_id)');
        $this->addSql('CREATE INDEX IDX_845CA2C1CFFE9AD6 ON order_details (orders_id)');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F52993986C755722 FOREIGN KEY (buyer_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F5299398F5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F529939812136921 FOREIGN KEY (delivery_id) REFERENCES transporter (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_details ADD CONSTRAINT FK_845CA2C14584665A FOREIGN KEY (product_id) REFERENCES article (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_details ADD CONSTRAINT FK_845CA2C1CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES "order" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE "order_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE order_details_id_seq CASCADE');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F52993986C755722');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F5299398F5B7AF75');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F529939812136921');
        $this->addSql('ALTER TABLE order_details DROP CONSTRAINT FK_845CA2C14584665A');
        $this->addSql('ALTER TABLE order_details DROP CONSTRAINT FK_845CA2C1CFFE9AD6');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('DROP TABLE order_details');
    }
}
