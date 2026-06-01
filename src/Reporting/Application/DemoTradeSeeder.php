<?php

declare(strict_types=1);

namespace Koersa\Reporting\Application;

use Koersa\Portfolio\Application\ImportTransactions;
use Koersa\Portfolio\Application\StatementParserRegistry;
use Koersa\Shared\Application\OrganizationSeeder;
use Koersa\Shared\Domain\Uuid;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

// Seeds a fresh org with the Kraken demo CSV via real ImportTransactions
// commands. Idempotent via the aggregate's (source, externalId) dedup.
// Best-effort: never throws — a seed failure must not block the user's
// registration. See ADR 0012.
#[AsAlias(OrganizationSeeder::class)]
final readonly class DemoTradeSeeder implements OrganizationSeeder
{
    private const string DEMO_CSV_PATH = __DIR__.'/../../../tests/Fixtures/Import/kraken_trades_demo.csv';

    public function __construct(
        private StatementParserRegistry $parsers,
        private MessageBusInterface $commandBus,
        private LoggerInterface $logger,
    ) {
    }

    public function seed(Uuid $organizationId): void
    {
        try {
            $contents = file_get_contents(self::DEMO_CSV_PATH);
            if (false === $contents) {
                $this->logger->warning('Demo CSV unreadable; skipping demo seed.', ['orgId' => (string) $organizationId]);

                return;
            }

            $trades = $this->parsers->parserFor('kraken')->parse($contents);
            $this->commandBus->dispatch(new ImportTransactions($organizationId, 'kraken', $trades));
        } catch (Throwable $e) {
            // Logged + swallowed so the registration commits regardless.
            $this->logger->error('Demo seed failed', [
                'orgId' => (string) $organizationId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
