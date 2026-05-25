<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application\Query;

use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;

/**
 * Computes per-asset holdings from an organization's transactions: net quantity
 * (buys − sells) and the weighted-average buy cost.
 *
 * MVP note: the aggregation uses floats for display only. Exact decimal math
 * (the tax engine, Iteration 5) will work off the stored NUMERIC values. In
 * Iteration 2 this becomes a rebuildable projection over the event stream.
 */
final class GetHoldings
{
    public function __construct(private readonly TransactionRepository $transactions)
    {
    }

    /**
     * @return list<Holding>
     */
    public function __invoke(Uuid $organizationId): array
    {
        /** @var array<string, float> $netQuantity */
        $netQuantity = [];
        /** @var array<string, float> $buyValue */
        $buyValue = [];
        /** @var array<string, float> $buyQuantity */
        $buyQuantity = [];

        foreach ($this->transactions->forOrganization($organizationId) as $transaction) {
            $asset = $transaction->asset;
            $quantity = (float) $transaction->quantity;

            $netQuantity[$asset] = ($netQuantity[$asset] ?? 0.0)
                + (Side::Buy === $transaction->side ? $quantity : -$quantity);

            if (Side::Buy === $transaction->side) {
                $buyValue[$asset] = ($buyValue[$asset] ?? 0.0) + $quantity * (float) $transaction->price;
                $buyQuantity[$asset] = ($buyQuantity[$asset] ?? 0.0) + $quantity;
            }
        }

        $holdings = [];
        foreach ($netQuantity as $asset => $net) {
            $boughtQuantity = $buyQuantity[$asset] ?? 0.0;
            $averageCost = $boughtQuantity > 0.0 ? ($buyValue[$asset] ?? 0.0) / $boughtQuantity : 0.0;

            $holdings[] = new Holding(
                $asset,
                rtrim(rtrim(number_format($net, 8, '.', ''), '0'), '.'),
                number_format($averageCost, 2, '.', ''),
            );
        }

        usort($holdings, static fn (Holding $a, Holding $b): int => $a->asset <=> $b->asset);

        return $holdings;
    }
}
