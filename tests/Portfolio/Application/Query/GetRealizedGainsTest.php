<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Application\Query;

use DateTimeImmutable;
use Koersa\Portfolio\Application\Query\GetRealizedGains;
use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;
use Koersa\Shared\Market\FxRateProvider;
use PHPUnit\Framework\TestCase;

final class GetRealizedGainsTest extends TestCase
{
    public function testFifoOnASingleAssetInEur(): void
    {
        $org = Uuid::generate();
        $transactions = $this->repo([
            $this->buy('BTC', '1', '20000', '0', '2024-01-01', $org),
            $this->buy('BTC', '1', '30000', '0', '2024-06-01', $org),
            // Sell 1.5 BTC at 40000 — consumes the 20k lot fully and half the 30k lot.
            $this->sell('BTC', '1.5', '40000', '0', '2025-01-01', $org),
        ]);

        $report = (new GetRealizedGains($transactions, $this->identityFx()))($org);

        self::assertCount(1, $report->perAsset);
        $btc = $report->perAsset[0];

        // proceeds = 1.5 * 40000 = 60000
        self::assertSame('60000', $btc->proceedsEur->amount);
        // cost basis = 1 * 20000 + 0.5 * 30000 = 35000
        self::assertSame('35000', $btc->costBasisEur->amount);
        // gain = 25000
        self::assertSame('25000', $btc->gainEur->amount);
        self::assertSame(0, $btc->unmatchedSellCount);
    }

    public function testFeesIncreaseCostBasisAndReduceProceeds(): void
    {
        $org = Uuid::generate();
        $transactions = $this->repo([
            $this->buy('ETH', '2', '1000', '10', '2024-01-01', $org),
            $this->sell('ETH', '2', '1500', '5', '2025-01-01', $org),
        ]);

        $report = (new GetRealizedGains($transactions, $this->identityFx()))($org);
        $eth = $report->perAsset[0];

        // cost = 2 * 1000 + 10 = 2010
        self::assertSame('2010', $eth->costBasisEur->amount);
        // proceeds = 2 * 1500 - 5 = 2995
        self::assertSame('2995', $eth->proceedsEur->amount);
        self::assertSame('985', $eth->gainEur->amount);
    }

    public function testUsesEcbRateOnTheTradeDateForNonEurLegs(): void
    {
        $org = Uuid::generate();
        $transactions = $this->repo([
            // Bought at $1000 when 1 USD = 0.9 EUR -> 900 EUR cost
            $this->buy('SOL', '1', '1000', '0', '2024-01-01', $org, priceCurrency: 'USD', feeCurrency: 'USD'),
            // Sold at $2000 when 1 USD = 1.0 EUR -> 2000 EUR proceeds
            $this->sell('SOL', '1', '2000', '0', '2025-01-01', $org, priceCurrency: 'USD', feeCurrency: 'USD'),
        ]);

        $fx = $this->stubFx([
            '2024-01-01' => ['USD->EUR' => '0.9'],
            '2025-01-01' => ['USD->EUR' => '1.0'],
        ]);

        $report = (new GetRealizedGains($transactions, $fx))($org);
        $sol = $report->perAsset[0];

        self::assertSame('900', $sol->costBasisEur->amount);
        self::assertSame('2000', $sol->proceedsEur->amount);
        self::assertSame('1100', $sol->gainEur->amount);
    }

    public function testFlagsSellsWithoutPriorBuys(): void
    {
        $org = Uuid::generate();
        $transactions = $this->repo([
            // No prior buy — the user transferred BTC in from a wallet we don't know about.
            $this->sell('BTC', '1', '50000', '0', '2025-01-01', $org),
        ]);

        $report = (new GetRealizedGains($transactions, $this->identityFx()))($org);
        $btc = $report->perAsset[0];

        self::assertSame(1, $btc->unmatchedSellCount);
        self::assertSame(1, $report->unmatchedSellCount);
        // The unmatched portion contributes zero to cost AND proceeds — we
        // refuse to invent a basis we can't defend.
        self::assertSame('0', $btc->proceedsEur->amount);
        self::assertSame('0', $btc->costBasisEur->amount);
    }

    public function testSinceFilterCountsOnlyInScopeSellsButStillFeedsLotsFromEarlierBuys(): void
    {
        $org = Uuid::generate();
        $transactions = $this->repo([
            $this->buy('BTC', '1', '20000', '0', '2024-01-01', $org),
            // Out-of-scope sell — feeds the FIFO queue but doesn't contribute to totals.
            $this->sell('BTC', '0.5', '30000', '0', '2024-06-01', $org),
            // In-scope sell — matched against the remaining 0.5 BTC of the first lot.
            $this->sell('BTC', '0.5', '40000', '0', '2025-03-01', $org),
        ]);

        $report = (new GetRealizedGains($transactions, $this->identityFx()))(
            $org,
            new DateTimeImmutable('2025-01-01'),
        );

        $btc = $report->perAsset[0];
        // cost basis of the in-scope leg = 0.5 * 20000 = 10000
        self::assertSame('10000', $btc->costBasisEur->amount);
        // proceeds = 0.5 * 40000 = 20000
        self::assertSame('20000', $btc->proceedsEur->amount);
        self::assertSame('10000', $btc->gainEur->amount);
    }

    public function testTotalsAggregateAcrossAssets(): void
    {
        $org = Uuid::generate();
        $transactions = $this->repo([
            $this->buy('BTC', '1', '20000', '0', '2024-01-01', $org),
            $this->sell('BTC', '1', '30000', '0', '2025-01-01', $org),
            $this->buy('ETH', '5', '1000', '0', '2024-01-01', $org),
            $this->sell('ETH', '5', '800', '0', '2025-01-01', $org),
        ]);

        $report = (new GetRealizedGains($transactions, $this->identityFx()))($org);

        // BTC +10000, ETH -1000 -> total gain +9000
        self::assertSame('9000', $report->totalGainEur->amount);
        // BTC proceeds 30000 + ETH proceeds 4000 = 34000
        self::assertSame('34000', $report->totalProceedsEur->amount);
        self::assertSame('25000', $report->totalCostBasisEur->amount);
    }

    /**
     * @param list<Transaction> $transactions in any order; the repo returns them most-recent-first
     */
    private function repo(array $transactions): TransactionRepository
    {
        return new InMemoryTransactionRepository($transactions);
    }

    private function identityFx(): FxRateProvider
    {
        return new class implements FxRateProvider {
            public function rateOn(DateTimeImmutable $date, string $from, string $to): string
            {
                return '1';
            }
        };
    }

    /**
     * @param array<string, array<string, string>> $byDay e.g. ['2024-01-01' => ['USD->EUR' => '0.9']]
     */
    private function stubFx(array $byDay): FxRateProvider
    {
        return new class($byDay) implements FxRateProvider {
            /** @param array<string, array<string, string>> $byDay */
            public function __construct(private readonly array $byDay)
            {
            }

            public function rateOn(DateTimeImmutable $date, string $from, string $to): string
            {
                if ($from === $to) {
                    return '1';
                }
                $day = $date->format('Y-m-d');
                $rate = $this->byDay[$day][$from.'->'.$to] ?? null;
                \assert(\is_string($rate), \sprintf('Missing stub rate for %s on %s', $from.'->'.$to, $day));

                return $rate;
            }
        };
    }

    private function buy(string $asset, string $qty, string $price, string $fee, string $date, Uuid $org, string $priceCurrency = 'EUR', string $feeCurrency = 'EUR'): Transaction
    {
        return Transaction::reconstitute(
            Uuid::generate(),
            $org,
            $asset,
            Side::Buy,
            $qty,
            $price,
            $fee,
            new DateTimeImmutable($date),
            'manual',
            null,
            $priceCurrency,
            $feeCurrency,
        );
    }

    private function sell(string $asset, string $qty, string $price, string $fee, string $date, Uuid $org, string $priceCurrency = 'EUR', string $feeCurrency = 'EUR'): Transaction
    {
        return Transaction::reconstitute(
            Uuid::generate(),
            $org,
            $asset,
            Side::Sell,
            $qty,
            $price,
            $fee,
            new DateTimeImmutable($date),
            'manual',
            null,
            $priceCurrency,
            $feeCurrency,
        );
    }
}
