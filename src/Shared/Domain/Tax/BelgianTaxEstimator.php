<?php

declare(strict_types=1);

namespace Koersa\Shared\Domain\Tax;

use InvalidArgumentException;
use Koersa\Shared\Domain\Money;

// Pure-function estimator: realized gain in EUR -> one scenario per Belgian
// regime. Never picks one; the user (or their accountant) does. See ADR 0007.
final readonly class BelgianTaxEstimator
{
    private const string SPECULATIVE_RATE = '0.33';

    /**
     * @return list<TaxEstimate>
     */
    public function estimate(Money $gainEur): array
    {
        if ('EUR' !== $gainEur->currency) {
            throw new InvalidArgumentException(\sprintf('Belgian tax estimates expect EUR; got %s.', $gainEur->currency));
        }

        $zero = Money::zero('EUR');
        // Losses don't create refunds under Belgian rules — show 0 with a note in the UI.
        $taxableGain = $gainEur->isPositive() ? $gainEur : $zero;

        return [
            new TaxEstimate(Regime::NormalManagement, $zero),
            new TaxEstimate(Regime::Speculative, $taxableGain->multiply(self::SPECULATIVE_RATE)),
            // Professional is progressive on the user's total income; we don't have that.
            new TaxEstimate(Regime::Professional, null),
        ];
    }
}
