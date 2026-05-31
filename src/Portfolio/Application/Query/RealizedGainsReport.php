<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application\Query;

use Koersa\Shared\Domain\Money;

final readonly class RealizedGainsReport
{
    /**
     * @param list<RealizedGain> $perAsset sorted alphabetically by asset
     */
    public function __construct(
        public array $perAsset,
        public Money $totalProceedsEur,
        public Money $totalCostBasisEur,
        public Money $totalGainEur,
        public int $unmatchedSellCount,
    ) {
    }
}
