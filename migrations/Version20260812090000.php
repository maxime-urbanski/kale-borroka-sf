<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add support.code, typed with the SupportType enum, so the list of formats has a single
 * source of truth instead of living in the table, the enum and two route requirements.
 *
 * The column is backfilled from support.name, which already holds the canonical values
 * (lp, ep, cd, fanzine, tape), before the NOT NULL and UNIQUE constraints are applied.
 */
final class Version20260812090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the support.code column typed with the SupportType enum';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE support ADD code VARCHAR(32) DEFAULT NULL');
        $this->addSql('UPDATE support SET code = name');

        // Any row whose name is not a valid SupportType value would break the enum
        // hydration, so fail loudly here rather than at runtime.
        $this->addSql(<<<'SQL'
            DO $$
            BEGIN
                IF EXISTS (SELECT 1 FROM support WHERE code IS NULL OR code NOT IN ('lp', 'ep', 'cd', 'fanzine', 'tape')) THEN
                    RAISE EXCEPTION 'support.name holds values that are not SupportType cases; fix them before migrating';
                END IF;
            END $$;
            SQL);

        $this->addSql('ALTER TABLE support ALTER code SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8004EBA577153098 ON support (code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8004EBA577153098');
        $this->addSql('ALTER TABLE support DROP code');
    }
}
