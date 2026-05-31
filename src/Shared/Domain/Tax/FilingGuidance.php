<?php

declare(strict_types=1);

namespace Koersa\Shared\Domain\Tax;

use Koersa\Shared\Domain\Money;

// Per-regime guidance for filling out the Belgian personal income tax
// declaration. boxLabelKey / codeLabelKey are translation keys; null means
// "no specific box to fill" (normal management — tax-free) or "we don't
// model it" (professional). See ADR 0008.
final readonly class FilingGuidance
{
    public function __construct(
        public Regime $regime,
        public int $incomeYear,
        public ?string $boxLabelKey,
        public ?string $codeLabelKey,
        public ?Money $amount,
        public string $noteKey,
    ) {
    }
}
