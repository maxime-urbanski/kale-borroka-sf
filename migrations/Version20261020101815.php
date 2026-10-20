<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Wantlist alerts: an e-mail when a record can be bought again. Off for the records already
 * in a wantlist (nobody asked for them yet), on for those added from now on (the entity's
 * default).
 */
final class Version20261020101815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Wantlist alert switch';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wishlist_item ADD alert_enabled BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wishlist_item DROP alert_enabled');
    }
}
