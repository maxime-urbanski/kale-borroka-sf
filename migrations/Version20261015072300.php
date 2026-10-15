<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * The motto after the footer's copyright in « Blocs de l'accueil ». An existing row gets
 * the current one.
 */
final class Version20261015072300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Footer motto';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE homepage ADD footer_tagline VARCHAR(120) DEFAULT NULL');
        $this->addSql("UPDATE homepage SET footer_tagline = 'Make racists afraid again'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE homepage DROP footer_tagline');
    }
}
