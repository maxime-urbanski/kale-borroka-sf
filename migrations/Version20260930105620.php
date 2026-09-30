<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * E-mails are now stored lowercased (User::normalizeEmail()). Stops rather than merging
 * accounts when two of them only differ by case: sort those out by hand first.
 */
final class Version20260930105620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lowercase user e-mails';
    }

    public function up(Schema $schema): void
    {
        $duplicates = $this->connection->fetchFirstColumn(
            'SELECT lower(trim(email)) FROM "user" GROUP BY 1 HAVING count(*) > 1',
        );

        $this->abortIf([] !== $duplicates, 'Accounts differing only by e-mail case: '.implode(', ', $duplicates));

        $this->addSql('UPDATE "user" SET email = lower(trim(email)) WHERE email <> lower(trim(email))');
    }

    public function down(Schema $schema): void
    {
        // The original case is lost; lowercase e-mails remain valid.
    }
}
