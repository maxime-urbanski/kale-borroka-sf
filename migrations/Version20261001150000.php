<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tracks get their position on a vinyl or a tape (A1, B3...), to show the tracklist by side.
 */
final class Version20261001150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Position of a track on its side (A1, B3...)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song ADD position VARCHAR(3) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song DROP position');
    }
}
