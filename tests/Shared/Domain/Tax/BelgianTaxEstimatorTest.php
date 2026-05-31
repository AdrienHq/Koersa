<?php

declare(strict_types=1);

namespace Koersa\Tests\Shared\Domain\Tax;

use InvalidArgumentException;
use Koersa\Shared\Domain\Money;
use Koersa\Shared\Domain\Tax\BelgianTaxEstimator;
use Koersa\Shared\Domain\Tax\Regime;
use PHPUnit\Framework\TestCase;

final class BelgianTaxEstimatorTest extends TestCase
{
    public function testProducesOneScenarioPerRegime(): void
    {
        $estimates = (new BelgianTaxEstimator())->estimate(Money::of('1000', 'EUR'));

        self::assertCount(3, $estimates);
        self::assertSame(Regime::NormalManagement, $estimates[0]->regime);
        self::assertSame(Regime::Speculative, $estimates[1]->regime);
        self::assertSame(Regime::Professional, $estimates[2]->regime);
    }

    public function testNormalManagementIsAlwaysTaxFree(): void
    {
        $estimate = (new BelgianTaxEstimator())->estimate(Money::of('1000', 'EUR'))[0];

        self::assertNotNull($estimate->amountEur);
        self::assertSame('0', $estimate->amountEur->amount);
        self::assertSame('EUR', $estimate->amountEur->currency);
    }

    public function testSpeculativeApplies33PercentOnAPositiveGain(): void
    {
        $estimate = (new BelgianTaxEstimator())->estimate(Money::of('1000', 'EUR'))[1];

        self::assertNotNull($estimate->amountEur);
        self::assertSame('330', $estimate->amountEur->amount);
    }

    public function testSpeculativeIsZeroOnALoss(): void
    {
        $estimate = (new BelgianTaxEstimator())->estimate(Money::of('-500', 'EUR'))[1];

        self::assertNotNull($estimate->amountEur);
        self::assertSame('0', $estimate->amountEur->amount);
    }

    public function testProfessionalAmountIsNullByDesign(): void
    {
        $estimate = (new BelgianTaxEstimator())->estimate(Money::of('1000', 'EUR'))[2];

        // Progressive on the user's total income — Koersa doesn't have that input.
        self::assertNull($estimate->amountEur);
    }

    public function testRejectsNonEurGain(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new BelgianTaxEstimator())->estimate(Money::of('1000', 'USD'));
    }
}
