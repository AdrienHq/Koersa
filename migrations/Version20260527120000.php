<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add trade provenance to the transactions read model (ADR 0004): where a trade
 * came from and the originating exchange row id.
 */
final class Version20260527120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add source and external_id to portfolio_transactions';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration targets PostgreSQL.',
        );

        $this->addSql("ALTER TABLE portfolio_transactions ADD source VARCHAR(16) NOT NULL DEFAULT 'manual'");
        $this->addSql('ALTER TABLE portfolio_transactions ADD external_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration targets PostgreSQL.',
        );

        $this->addSql('ALTER TABLE portfolio_transactions DROP source');
        $this->addSql('ALTER TABLE portfolio_transactions DROP external_id');
    }
}
