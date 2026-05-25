<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Portfolio context: the manual (pre-event-sourcing) transactions table.
 */
final class Version20260525120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create portfolio_transactions';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration targets PostgreSQL.',
        );

        $this->addSql('CREATE TABLE portfolio_transactions (id UUID NOT NULL, organization_id UUID NOT NULL, asset VARCHAR(12) NOT NULL, side VARCHAR(8) NOT NULL, quantity NUMERIC(36, 18) NOT NULL, price NUMERIC(36, 18) NOT NULL, fee NUMERIC(36, 18) NOT NULL, occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_portfolio_tx_org ON portfolio_transactions (organization_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration targets PostgreSQL.',
        );

        $this->addSql('DROP TABLE portfolio_transactions');
    }
}
