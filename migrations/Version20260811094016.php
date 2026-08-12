<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Clear the "(DC2Type:...)" column comments that DBAL 3 used to store the Doctrine type in,
 * and add the missing default on user.created_at that the entity mapping already declares.
 */
final class Version20260811094016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop DBAL 3 DC2Type column comments and set the user.created_at default';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('COMMENT ON COLUMN article.created_at IS \'\'');
        $this->addSql('COMMENT ON COLUMN article.updated_at IS \'\'');
        $this->addSql('COMMENT ON COLUMN image.updated_at IS \'\'');
        $this->addSql('COMMENT ON COLUMN "order".created_at IS \'\'');
        $this->addSql('COMMENT ON COLUMN reset_password_request.requested_at IS \'\'');
        $this->addSql('COMMENT ON COLUMN reset_password_request.expires_at IS \'\'');
        $this->addSql('ALTER TABLE "user" ALTER created_at SET DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'\'');
        $this->addSql('COMMENT ON COLUMN user_collection_items.added_at IS \'\'');
        $this->addSql('COMMENT ON COLUMN wishlist_item.added_at IS \'\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('COMMENT ON COLUMN article.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN article.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN image.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "order".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN reset_password_request.requested_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN reset_password_request.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE "user" ALTER created_at DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_collection_items.added_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN wishlist_item.added_at IS \'(DC2Type:datetime_immutable)\'');
    }
}
