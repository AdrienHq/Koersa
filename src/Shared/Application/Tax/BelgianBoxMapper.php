<?php

declare(strict_types=1);

namespace Koersa\Shared\Application\Tax;

use Koersa\Shared\Domain\Money;
use Koersa\Shared\Domain\Tax\FilingGuidance;
use Koersa\Shared\Domain\Tax\Regime;

// Per-regime, per-income-year guidance on where the realized gain goes on the
// Belgian declaration. See ADR 0008. Codes shift year to year; verifying
// against SPF Finance / FOD Financiën documentation before the filing season
// is a launch-checklist item.
final readonly class BelgianBoxMapper
{
    private const int FIRST_SUPPORTED_INCOME_YEAR = 2024;

    public function guide(Regime $regime, Money $gainEur, int $incomeYear): FilingGuidance
    {
        if ($incomeYear < self::FIRST_SUPPORTED_INCOME_YEAR) {
            return $this->unsupportedYear($regime, $incomeYear);
        }

        return match ($regime) {
            Regime::NormalManagement => new FilingGuidance(
                regime: $regime,
                incomeYear: $incomeYear,
                boxLabelKey: null,
                codeLabelKey: null,
                amount: null,
                noteKey: 'tax_filing.normal_management.note',
            ),
            Regime::Speculative => new FilingGuidance(
                regime: $regime,
                incomeYear: $incomeYear,
                boxLabelKey: 'tax_filing.speculative.box',
                codeLabelKey: 'tax_filing.speculative.codes',
                // The user enters the gain (gross). The SPF runs the 33%.
                amount: $gainEur->isPositive() ? $gainEur : Money::zero('EUR'),
                noteKey: 'tax_filing.speculative.note',
            ),
            Regime::Professional => new FilingGuidance(
                regime: $regime,
                incomeYear: $incomeYear,
                boxLabelKey: null,
                codeLabelKey: null,
                amount: null,
                noteKey: 'tax_filing.professional.note',
            ),
        };
    }

    private function unsupportedYear(Regime $regime, int $incomeYear): FilingGuidance
    {
        return new FilingGuidance(
            regime: $regime,
            incomeYear: $incomeYear,
            boxLabelKey: null,
            codeLabelKey: null,
            amount: null,
            noteKey: 'tax_filing.unsupported_year.note',
        );
    }
}
