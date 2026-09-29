<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * CMS pages: where each one is linked in the footer, and in which order.
 * Existing pages stay out of the footer until placed from the back office.
 */
final class Version20260929110949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Page footer placement and position';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page ADD footer_placement VARCHAR(20) DEFAULT \'none\' NOT NULL');
        $this->addSql('ALTER TABLE page ADD footer_position INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page DROP footer_placement');
        $this->addSql('ALTER TABLE page DROP footer_position');
    }
}
