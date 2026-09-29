<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Order workflow: status becomes an OrderStatus value, payment status and date are added,
 * and order lines keep a copy of the article's name, SKU and unit price.
 *
 * Existing orders were all created with the free-text status 'PROCESS' and never paid
 * through the site: they become pending.
 */
final class Version20260929094632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Order workflow status, payment status, order line snapshot';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE "order" SET status = 'pending'
            WHERE status NOT IN ('pending', 'paid', 'preparing', 'shipped', 'delivered', 'cancelled', 'refunded')
            SQL);
        $this->addSql('ALTER TABLE "order" ALTER status SET DEFAULT \'pending\'');
        $this->addSql('ALTER TABLE "order" ADD payment_status VARCHAR(20) DEFAULT \'awaiting\' NOT NULL');
        $this->addSql('ALTER TABLE "order" ADD paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F5299398AEA34913 ON "order" (reference)');

        $this->addSql('ALTER TABLE order_details ADD unit_price INT DEFAULT NULL');
        $this->addSql('ALTER TABLE order_details ADD product_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE order_details ADD sku VARCHAR(64) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE order_details d
            SET product_name = a.name, sku = a.sku, unit_price = d.price / GREATEST(d.quantity, 1)
            FROM article a WHERE a.id = d.product_id
            SQL);
        $this->addSql('ALTER TABLE order_details ALTER unit_price SET NOT NULL');
        $this->addSql('ALTER TABLE order_details ALTER product_name SET NOT NULL');
        $this->addSql('ALTER TABLE order_details ALTER sku SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_details DROP unit_price');
        $this->addSql('ALTER TABLE order_details DROP product_name');
        $this->addSql('ALTER TABLE order_details DROP sku');

        $this->addSql('DROP INDEX UNIQ_F5299398AEA34913');
        $this->addSql('ALTER TABLE "order" DROP payment_status');
        $this->addSql('ALTER TABLE "order" DROP paid_at');
        $this->addSql('ALTER TABLE "order" ALTER status DROP DEFAULT');
    }
}
