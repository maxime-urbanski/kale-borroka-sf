<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Orders now charge shipping. Existing orders keep 0: their total never included it.
 */
final class Version20260930110923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Shipping price of the orders';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" ADD shipping_price INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" DROP shipping_price');
    }
}
