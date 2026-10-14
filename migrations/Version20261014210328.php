<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * « Dernières prod » and « Derniers articles » settings in « Blocs de l'accueil ». An existing
 * row gets the defaults of Homepage, links and every shelf included.
 */
final class Version20261014210328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Homepage sections settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE homepage ADD productions_shown BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE homepage ADD productions_title VARCHAR(60) DEFAULT \'Dernières prod\' NOT NULL');
        $this->addSql('ALTER TABLE homepage ADD productions_limit INT DEFAULT 4 NOT NULL');
        $this->addSql('ALTER TABLE homepage ADD productions_link_text VARCHAR(60) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage ADD latest_shown BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE homepage ADD latest_title VARCHAR(60) DEFAULT \'Derniers articles\' NOT NULL');
        $this->addSql('ALTER TABLE homepage ADD latest_limit INT DEFAULT 6 NOT NULL');
        $this->addSql('ALTER TABLE homepage ADD latest_link_text VARCHAR(60) DEFAULT NULL');
        $this->addSql('ALTER TABLE homepage ADD latest_shelves JSON DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE homepage SET
                productions_link_text = 'Tout le catalogue du label',
                latest_link_text = 'Tous les arrivages',
                latest_shelves = '["tout", "vinyles", "cd", "k7", "zines", "occasion"]'
            SQL);
        $this->addSql('ALTER TABLE homepage ALTER latest_shelves SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE homepage DROP productions_shown');
        $this->addSql('ALTER TABLE homepage DROP productions_title');
        $this->addSql('ALTER TABLE homepage DROP productions_limit');
        $this->addSql('ALTER TABLE homepage DROP productions_link_text');
        $this->addSql('ALTER TABLE homepage DROP latest_shown');
        $this->addSql('ALTER TABLE homepage DROP latest_title');
        $this->addSql('ALTER TABLE homepage DROP latest_limit');
        $this->addSql('ALTER TABLE homepage DROP latest_link_text');
        $this->addSql('ALTER TABLE homepage DROP latest_shelves');
    }
}
