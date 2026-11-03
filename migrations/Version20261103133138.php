<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Slugs on styles and labels, for readable catalogue filter URLs (?styles[]=punk). Existing
 * rows get one computed like Gedmo's (lowercase ASCII, dashes); a duplicate gets « --<id> »
 * appended, which no computed slug can be (dashes are collapsed). New rows get theirs from Gedmo\Slug.
 */
final class Version20261103133138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Slug on style and label (catalogue filters)';
    }

    public function up(Schema $schema): void
    {
        foreach (['label' => 'UNIQ_EA750E8989D9B62', 'style' => 'UNIQ_33BDB86A989D9B62'] as $table => $index) {
            $this->addSql(\sprintf('ALTER TABLE %s ADD slug VARCHAR(255) DEFAULT NULL', $table));
            $this->addSql(\sprintf(<<<'SQL'
                UPDATE %1$s SET slug = computed.slug FROM (
                    SELECT id, CASE WHEN ROW_NUMBER() OVER (PARTITION BY base ORDER BY id) = 1 THEN base ELSE base || '--' || id END AS slug
                    FROM (
                        SELECT id, COALESCE(NULLIF(TRIM(BOTH '-' FROM REGEXP_REPLACE(
                            TRANSLATE(LOWER(name), 'àâäáãåçéèêëíìîïñóòôöõúùûüýÿ', 'aaaaaaceeeeiiiinooooouuuuyy'),
                            '[^a-z0-9]+', '-', 'g'
                        )), ''), 'n-a') AS base
                        FROM %1$s
                    ) AS bases
                ) AS computed
                WHERE %1$s.id = computed.id
                SQL, $table));
            $this->addSql(\sprintf('ALTER TABLE %s ALTER slug SET NOT NULL', $table));
            $this->addSql(\sprintf('CREATE UNIQUE INDEX %s ON %s (slug)', $index, $table));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_EA750E8989D9B62');
        $this->addSql('ALTER TABLE label DROP slug');
        $this->addSql('DROP INDEX UNIQ_33BDB86A989D9B62');
        $this->addSql('ALTER TABLE style DROP slug');
    }
}
