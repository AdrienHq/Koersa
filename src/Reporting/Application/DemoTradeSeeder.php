<?php

declare(strict_types=1);

namespace Koersa\Reporting\Application;

use DateTimeImmutable;
use Koersa\Portfolio\Application\ImportTransactions;
use Koersa\Portfolio\Application\ParsedTrade;
use Koersa\Portfolio\Application\StatementParserRegistry;
use Koersa\Shared\Application\OrganizationSeeder;
use Koersa\Shared\Domain\Uuid;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

// Seeds a fresh org with the Kraken demo CSV via real ImportTransactions
// commands. Idempotent via the aggregate's (source, externalId) dedup.
// Best-effort: never throws — a seed failure must not block the user's
// registration. See ADR 0012.
//
// Trade dates are *rebased* on the fly so the most recent trade lands
// ~30 days before today. The CSV stays stable; the seeded portfolio
// stays fresh as the clock moves. Without this the activity chart, the
// 'most recent year with sells' detection and other date-sensitive UI
// drift into showing empty data as time passes.
#[AsAlias(OrganizationSeeder::class)]
final readonly class DemoTradeSeeder implements OrganizationSeeder
{
    private const string DEMO_CSV_PATH = __DIR__.'/../../../tests/Fixtures/Import/kraken_trades_demo.csv';
    private const int MOST_RECENT_OFFSET_DAYS = 30;

    public function __construct(
        private StatementParserRegistry $parsers,
        private MessageBusInterface $commandBus,
        private ClockInterface $clock,
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
            $trades = $this->rebaseToRecent($trades);
            $this->commandBus->dispatch(new ImportTransactions($organizationId, 'kraken', $trades));
        } catch (Throwable $e) {
            // Logged + swallowed so the registration commits regardless.
            $this->logger->error('Demo seed failed', [
                'orgId' => (string) $organizationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Shifts every trade so the most recent one is ~30 days ago, preserving
     * relative spacing. No-op if the CSV is empty.
     *
     * @param list<ParsedTrade> $trades
     *
     * @return list<ParsedTrade>
     */
    private function rebaseToRecent(array $trades): array
    {
        if ([] === $trades) {
            return $trades;
        }

        $maxOccurredAt = $trades[0]->occurredAt;
        foreach ($trades as $trade) {
            if ($trade->occurredAt > $maxOccurredAt) {
                $maxOccurredAt = $trade->occurredAt;
            }
        }

        $target = $this->clock->now()->modify('-'.self::MOST_RECENT_OFFSET_DAYS.' days');
        $offsetSeconds = $target->getTimestamp() - $maxOccurredAt->getTimestamp();

        if (0 === $offsetSeconds) {
            return $trades;
        }

        return array_map(
            static fn (ParsedTrade $trade): ParsedTrade => new ParsedTrade(
                $trade->externalId,
                $trade->asset,
                $trade->side,
                $trade->quantity,
                $trade->price,
                $trade->fee,
                self::shift($trade->occurredAt, $offsetSeconds),
                $trade->priceCurrency,
                $trade->feeCurrency,
            ),
            $trades,
        );
    }

    private static function shift(DateTimeImmutable $instant, int $seconds): DateTimeImmutable
    {
        return $instant->modify(\sprintf('%+d seconds', $seconds));
    }
}
