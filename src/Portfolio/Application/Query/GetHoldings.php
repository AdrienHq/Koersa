<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application\Query;

use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;
use Koersa\Shared\Market\PriceProvider;

/**
 * Per-asset holdings (net quantity, weighted-average buy cost). Floats here are
 * display-only; exact decimal math belongs to the tax engine.
 */
final class GetHoldings
{
    public function __construct(
        private readonly TransactionRepository $transactions,
        private readonly PriceProvider $prices,
    ) {
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

        $heldAssets = [];
        foreach ($netQuantity as $asset => $net) {
            if ($net > 0.0) {
                $heldAssets[] = $asset;
            }
        }
        $eurPrices = [] !== $heldAssets ? $this->prices->pricesInEur($heldAssets) : [];

        $holdings = [];
        foreach ($netQuantity as $asset => $net) {
            // Hide closed or "negative" positions (more sold than we know was
            // bought — usually means imports miss earlier wallet history).
            if ($net <= 0.0) {
                continue;
            }

            $boughtQuantity = $buyQuantity[$asset] ?? 0.0;
            $averageCost = $boughtQuantity > 0.0 ? ($buyValue[$asset] ?? 0.0) / $boughtQuantity : 0.0;

            $price = $eurPrices[$asset] ?? null;
            $value = null !== $price ? number_format($net * (float) $price, 2, '.', '') : null;

            $holdings[] = new Holding(
                $asset,
                rtrim(rtrim(number_format($net, 8, '.', ''), '0'), '.'),
                number_format($averageCost, 2, '.', ''),
                $price,
                $value,
            );
        }

        usort($holdings, static fn (Holding $a, Holding $b): int => $a->asset <=> $b->asset);

        return $holdings;
    }
}
