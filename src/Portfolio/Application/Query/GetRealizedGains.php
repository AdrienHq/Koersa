<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application\Query;

use DateTimeImmutable;
use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Money;
use Koersa\Shared\Domain\Uuid;
use Koersa\Shared\Market\FxRateProvider;

// FIFO realized-gains report (ADR 0006). Walks every transaction for the
// organisation in chronological order, converts each leg to EUR at the trade
// date via the ECB, and matches sells against the oldest open buy lots first.
// Sells whose quantity exceeds the available lots at the time are flagged
// (rather than silently zero-cost-basis) — almost always a hint of missing
// import history.
final class GetRealizedGains
{
    public function __construct(
        private readonly TransactionRepository $transactions,
        private readonly FxRateProvider $fx,
    ) {
    }

    /**
     * Picks the most recent calendar year that had a sell and computes the
     * report scoped to that year. Returns [null, null] for an organisation
     * with no sells. Centralises the year-detection logic so every page that
     * shows realized-gains numbers picks the same year.
     *
     * @return array{0: ?RealizedGainsReport, 1: ?int}
     */
    public function forMostRecentYear(Uuid $organizationId): array
    {
        $year = $this->mostRecentSellYear($organizationId);
        if (null === $year) {
            return [null, null];
        }

        $since = new DateTimeImmutable($year.'-01-01T00:00:00+00:00');

        return [$this->__invoke($organizationId, $since), $year];
    }

    /**
     * @param ?DateTimeImmutable $since when set, only sells on or after this
     *                                  date contribute to the totals; earlier
     *                                  buys still feed the lot queue
     */
    public function __invoke(Uuid $organizationId, ?DateTimeImmutable $since = null): RealizedGainsReport
    {
        /** @var array<string, list<Transaction>> $byAsset */
        $byAsset = [];
        foreach ($this->transactions->forOrganization($organizationId) as $transaction) {
            $byAsset[$transaction->asset][] = $transaction;
        }

        $perAsset = [];
        $totalProceeds = Money::zero('EUR');
        $totalCost = Money::zero('EUR');
        $totalGain = Money::zero('EUR');
        $totalUnmatched = 0;

        foreach ($byAsset as $asset => $transactions) {
            usort($transactions, static fn (Transaction $a, Transaction $b): int => $a->occurredAt <=> $b->occurredAt);

            $result = $this->processAsset($asset, $transactions, $since);
            if (null === $result) {
                continue;
            }

            $perAsset[] = $result;
            $totalProceeds = $totalProceeds->add($result->proceedsEur);
            $totalCost = $totalCost->add($result->costBasisEur);
            $totalGain = $totalGain->add($result->gainEur);
            $totalUnmatched += $result->unmatchedSellCount;
        }

        usort($perAsset, static fn (RealizedGain $a, RealizedGain $b): int => $a->asset <=> $b->asset);

        return new RealizedGainsReport($perAsset, $totalProceeds, $totalCost, $totalGain, $totalUnmatched);
    }

    /**
     * @param list<Transaction> $transactions chronological order
     */
    private function processAsset(string $asset, array $transactions, ?DateTimeImmutable $since): ?RealizedGain
    {
        /** @var list<array{qty: numeric-string, cost: Money}> $lots */
        $lots = [];
        $proceeds = Money::zero('EUR');
        $cost = Money::zero('EUR');
        $unmatched = 0;
        $hadActivity = false;

        foreach ($transactions as $transaction) {
            $qty = $transaction->quantity;
            \assert(is_numeric($qty));
            $priceEur = $this->convertToEur($transaction->price, $transaction->priceCurrency, $transaction->occurredAt);
            $feeEur = $this->convertToEur($transaction->fee, $transaction->feeCurrency, $transaction->occurredAt);

            if (Side::Buy === $transaction->side) {
                // Fees on a buy add to the lot's cost basis.
                $lotCost = $priceEur->multiply($qty)->add($feeEur);
                $lots[] = ['qty' => $qty, 'cost' => $lotCost];
                continue;
            }

            // SELL — fees reduce the proceeds. Allocate proceeds per portion
            // directly (price*take - fee*share) instead of pre-computing the
            // leg total and dividing back, which would lose precision on a
            // bcdiv-then-bcmul round-trip (e.g. 1 / 1.5 truncated at scale 18).
            $sellInScope = null === $since || $transaction->occurredAt >= $since;
            $remaining = $qty;

            while (1 === bccomp($remaining, '0', 18) && [] !== $lots) {
                $headIndex = array_key_first($lots);
                $lot = $lots[$headIndex];

                if (-1 !== bccomp($remaining, $lot['qty'], 18)) {
                    // remaining >= lot.qty: consume the whole lot
                    $take = $lot['qty'];
                    $portionCost = $lot['cost'];
                    array_shift($lots);
                } else {
                    // remaining < lot.qty: pro-rata the lot's cost
                    $take = $remaining;
                    $share = self::numeric(bcdiv($take, $lot['qty'], 18));
                    $portionCost = $lot['cost']->multiply($share);
                    $lots[$headIndex] = [
                        'qty' => self::numeric(bcsub($lot['qty'], $take, 18)),
                        'cost' => $lot['cost']->subtract($portionCost),
                    ];
                }

                if ($sellInScope) {
                    $feeShare = self::numeric(bcdiv($take, $qty, 18));
                    $portionProceeds = $priceEur->multiply($take)->subtract($feeEur->multiply($feeShare));

                    $proceeds = $proceeds->add($portionProceeds);
                    $cost = $cost->add($portionCost);
                    $hadActivity = true;
                }

                $remaining = self::numeric(bcsub($remaining, $take, 18));
            }

            if (1 === bccomp($remaining, '0', 18) && $sellInScope) {
                ++$unmatched;
                $hadActivity = true;
            }
        }

        if (!$hadActivity) {
            return null;
        }

        return new RealizedGain(
            $asset,
            $proceeds,
            $cost,
            $proceeds->subtract($cost),
            $unmatched,
        );
    }

    private function mostRecentSellYear(Uuid $organizationId): ?int
    {
        $best = null;
        foreach ($this->transactions->forOrganization($organizationId) as $transaction) {
            if (Side::Sell !== $transaction->side) {
                continue;
            }
            $year = (int) $transaction->occurredAt->format('Y');
            if (null === $best || $year > $best) {
                $best = $year;
            }
        }

        return $best;
    }

    private function convertToEur(string $amount, string $currency, DateTimeImmutable $on): Money
    {
        $money = Money::of($amount, $currency);
        if ('EUR' === $money->currency) {
            return $money;
        }

        return $money->convertedTo('EUR', $this->fx->rateOn($on, $money->currency, 'EUR'));
    }

    /**
     * @return numeric-string
     */
    private static function numeric(string $value): string
    {
        \assert(is_numeric($value));

        return $value;
    }
}
