<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add the price and fee currencies to the transactions read model (ADR 0006).
 * Legacy rows default to EUR — the historical assumption that produced them.
 */
final class Version20260531120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add price_currency and fee_currency to portfolio_transactions';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration targets PostgreSQL.',
        );

        $this->addSql("ALTER TABLE portfolio_transactions ADD price_currency VARCHAR(8) NOT NULL DEFAULT 'EUR'");
        $this->addSql("ALTER TABLE portfolio_transactions ADD fee_currency VARCHAR(8) NOT NULL DEFAULT 'EUR'");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration targets PostgreSQL.',
        );

        $this->addSql('ALTER TABLE portfolio_transactions DROP price_currency');
        $this->addSql('ALTER TABLE portfolio_transactions DROP fee_currency');
    }
}
