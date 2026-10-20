<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Address book (maquette 13): each address names its recipient, and may carry a phone number
 * for the carrier. The existing addresses take the names of the account they belong to.
 */
final class Version20261020111853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recipient and phone on addresses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE address ADD firstname VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE address ADD lastname VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE address ADD telephone VARCHAR(30) DEFAULT NULL');
        $this->addSql('UPDATE address SET firstname = u.firstname, lastname = u.lastname FROM "user" u WHERE u.id = address.users_id');
        $this->addSql('ALTER TABLE address ALTER firstname SET NOT NULL');
        $this->addSql('ALTER TABLE address ALTER lastname SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE address DROP firstname');
        $this->addSql('ALTER TABLE address DROP lastname');
        $this->addSql('ALTER TABLE address DROP telephone');
    }
}
