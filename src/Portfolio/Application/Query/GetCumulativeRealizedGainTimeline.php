<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application\Query;

use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Money;
use Koersa\Shared\Domain\Uuid;
use Koersa\Shared\Market\FxRateProvider;

// FIFO walk over every transaction, emitting one point per sell with the
// running EUR realized-gain total. The dashboard chart consumes this. Same
// math as GetRealizedGains (deliberately duplicated — a second consumer of
// the same FIFO event-stream is the right shape to reach for once we extract
// a shared core).
final class GetCumulativeRealizedGainTimeline
{
    public function __construct(
        private readonly TransactionRepository $transactions,
        private readonly FxRateProvider $fx,
    ) {
    }

    /**
     * @return list<array{date: string, runningGainEur: string}>
     */
    public function __invoke(Uuid $organizationId): array
    {
        /** @var list<Transaction> $sorted */
        $sorted = $this->transactions->forOrganization($organizationId);
        usort($sorted, static fn (Transaction $a, Transaction $b): int => $a->occurredAt <=> $b->occurredAt);

        /** @var array<string, list<array{qty: numeric-string, cost: Money}>> $lotsByAsset */
        $lotsByAsset = [];
        $running = Money::zero('EUR');
        $points = [];

        foreach ($sorted as $transaction) {
            $qty = $transaction->quantity;
            \assert(is_numeric($qty));
            $priceEur = $this->convertToEur($transaction->price, $transaction->priceCurrency, $transaction);
            $feeEur = $this->convertToEur($transaction->fee, $transaction->feeCurrency, $transaction);

            if (Side::Buy === $transaction->side) {
                $lotCost = $priceEur->multiply($qty)->add($feeEur);
                $lotsByAsset[$transaction->asset][] = ['qty' => $qty, 'cost' => $lotCost];
                continue;
            }

            $remaining = $qty;
            $cost = Money::zero('EUR');
            $proceeds = Money::zero('EUR');
            $lots = $lotsByAsset[$transaction->asset] ?? [];

            while (1 === bccomp($remaining, '0', 18) && [] !== $lots) {
                $headIndex = array_key_first($lots);
                $lot = $lots[$headIndex];

                if (-1 !== bccomp($remaining, $lot['qty'], 18)) {
                    $take = $lot['qty'];
                    $portionCost = $lot['cost'];
                    array_shift($lots);
                } else {
                    $take = $remaining;
                    $share = self::numeric(bcdiv($take, $lot['qty'], 18));
                    $portionCost = $lot['cost']->multiply($share);
                    $lots[$headIndex] = [
                        'qty' => self::numeric(bcsub($lot['qty'], $take, 18)),
                        'cost' => $lot['cost']->subtract($portionCost),
                    ];
                }

                $feeShare = self::numeric(bcdiv($take, $qty, 18));
                $portionProceeds = $priceEur->multiply($take)->subtract($feeEur->multiply($feeShare));

                $cost = $cost->add($portionCost);
                $proceeds = $proceeds->add($portionProceeds);

                $remaining = self::numeric(bcsub($remaining, $take, 18));
            }

            $lotsByAsset[$transaction->asset] = $lots;

            $running = $running->add($proceeds->subtract($cost));
            $points[] = [
                'date' => $transaction->occurredAt->format('Y-m-d'),
                'runningGainEur' => $running->amount,
            ];
        }

        return $points;
    }

    private function convertToEur(string $amount, string $currency, Transaction $transaction): Money
    {
        $money = Money::of($amount, $currency);
        if ('EUR' === $money->currency) {
            return $money;
        }

        return $money->convertedTo('EUR', $this->fx->rateOn($transaction->occurredAt, $money->currency, 'EUR'));
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
