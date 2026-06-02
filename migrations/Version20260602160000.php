<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the paid-tier flag to users — the second door in front of paid
 * features, alongside ROLE_ADMIN bypass and (future) Stripe subscription.
 * Granted via the `iam:user:promote-paid` console command.
 */
final class Version20260602160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_paid flag to iam_users';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration targets PostgreSQL.',
        );

        $this->addSql('ALTER TABLE iam_users ADD is_paid BOOLEAN NOT NULL DEFAULT FALSE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration targets PostgreSQL.',
        );

        $this->addSql('ALTER TABLE iam_users DROP is_paid');
    }
}
