<?php

declare(strict_types=1);

namespace Koersa\Shared\Domain\Tax;

use Koersa\Shared\Domain\Money;

// One scenario for a single regime. `amountEur` is null when the regime needs
// inputs Koersa doesn't have (the professional case — see ADR 0007).
final readonly class TaxEstimate
{
    public function __construct(
        public Regime $regime,
        public ?Money $amountEur,
    ) {
    }
}
