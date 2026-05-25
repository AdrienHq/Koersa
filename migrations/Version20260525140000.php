<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Portfolio context: the event store (ADR 0002). portfolio_transactions stays
 * as-is but is now a read-model projection rebuilt from this stream.
 */
final class Version20260525140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create portfolio_event_store';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration targets PostgreSQL.',
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE portfolio_event_store (
                no BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
                aggregate_root_id UUID NOT NULL,
                aggregate_root_version INT NOT NULL,
                event_type VARCHAR(255) NOT NULL,
                payload JSONB NOT NULL,
                recorded_at TIMESTAMP(6) WITH TIME ZONE NOT NULL,
                PRIMARY KEY(no)
            )
            SQL);
        $this->addSql('CREATE UNIQUE INDEX uniq_portfolio_event_stream ON portfolio_event_store (aggregate_root_id, aggregate_root_version)');
        $this->addSql('CREATE INDEX idx_portfolio_event_aggregate ON portfolio_event_store (aggregate_root_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration targets PostgreSQL.',
        );

        $this->addSql('DROP TABLE portfolio_event_store');
    }
}
