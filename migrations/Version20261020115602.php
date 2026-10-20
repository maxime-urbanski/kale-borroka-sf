<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * E-mail change (maquette 14): the address waiting for its confirmation link, so that only the
 * latest link works, once.
 */
final class Version20261020115602 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Pending e-mail on users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD pending_email VARCHAR(180) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP pending_email');
    }
}
