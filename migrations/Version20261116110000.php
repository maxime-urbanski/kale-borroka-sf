<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * The buyer's optional message at checkout (« Un mot pour nous ? »).
 */
final class Version20261116110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Customer message on orders';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" ADD customer_message VARCHAR(280) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" DROP customer_message');
    }
}
