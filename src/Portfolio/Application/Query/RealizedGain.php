<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application\Query;

use Koersa\Shared\Domain\Money;

final readonly class RealizedGain
{
    public function __construct(
        public string $asset,
        public Money $proceedsEur,
        public Money $costBasisEur,
        public Money $gainEur,
        // sells whose quantity exceeded the prior buys at the time they happened
        public int $unmatchedSellCount,
    ) {
    }
}
