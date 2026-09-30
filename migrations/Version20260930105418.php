<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Orders keep a copy of their delivery address, so that editing or deleting an address
 * book entry no longer changes past orders (nor fails on the foreign key).
 */
final class Version20260930105418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Copy the delivery address into the order';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT fk_f5299398f5b7af75');
        $this->addSql('ALTER TABLE "order" ADD shipping_address TEXT DEFAULT NULL');
        // Same lines as Address::__toString().
        $this->addSql(<<<'SQL'
            UPDATE "order" o
            SET shipping_address = concat_ws(E'\n', a.name, a.address, NULLIF(a.complement_address, ''), NULLIF(trim(a.zipcode || ' ' || a.city), ''), a.country)
            FROM address a
            WHERE a.id = o.address_id
            SQL);
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F5299398F5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F5299398F5B7AF75');
        $this->addSql('ALTER TABLE "order" DROP shipping_address');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT fk_f5299398f5b7af75 FOREIGN KEY (address_id) REFERENCES address (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
