<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Label funds: expenses with their invoice (schema.org/Invoice), event sales
 * (schema.org/SellAction) and the opening balance of the shop settings.
 */
final class Version20260930090935 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expenses, event sales and opening balance of the label funds';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE event_sale_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE expense_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE event_sale (id INT NOT NULL, name VARCHAR(255) NOT NULL, location VARCHAR(255) DEFAULT NULL, start_time DATE NOT NULL, price INT NOT NULL, description TEXT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE expense (id INT NOT NULL, name VARCHAR(255) NOT NULL, category VARCHAR(20) NOT NULL, total_payment_due INT NOT NULL, payment_due_date DATE NOT NULL, provider VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, invoice_name VARCHAR(255) DEFAULT NULL, invoice_original_name VARCHAR(255) DEFAULT NULL, invoice_encoding_format VARCHAR(100) DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE shop_settings ADD opening_balance INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE shop_settings ADD opening_balance_date DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE event_sale_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE expense_id_seq CASCADE');
        $this->addSql('DROP TABLE event_sale');
        $this->addSql('DROP TABLE expense');
        $this->addSql('ALTER TABLE shop_settings DROP opening_balance');
        $this->addSql('ALTER TABLE shop_settings DROP opening_balance_date');
    }
}
