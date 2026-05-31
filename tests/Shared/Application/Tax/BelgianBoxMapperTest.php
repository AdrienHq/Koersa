<?php

declare(strict_types=1);

namespace Koersa\Tests\Shared\Application\Tax;

use Koersa\Shared\Application\Tax\BelgianBoxMapper;
use Koersa\Shared\Domain\Money;
use Koersa\Shared\Domain\Tax\Regime;
use PHPUnit\Framework\TestCase;

final class BelgianBoxMapperTest extends TestCase
{
    public function testNormalManagementHasNoBoxAndNoAmount(): void
    {
        $guidance = (new BelgianBoxMapper())->guide(Regime::NormalManagement, Money::of('1000', 'EUR'), 2024);

        self::assertSame(Regime::NormalManagement, $guidance->regime);
        self::assertSame(2024, $guidance->incomeYear);
        self::assertNull($guidance->boxLabelKey);
        self::assertNull($guidance->codeLabelKey);
        self::assertNull($guidance->amount);
        self::assertSame('tax_filing.normal_management.note', $guidance->noteKey);
    }

    public function testSpeculativePointsAtCadreXvAndSurfacesTheGain(): void
    {
        $guidance = (new BelgianBoxMapper())->guide(Regime::Speculative, Money::of('1000', 'EUR'), 2024);

        self::assertSame('tax_filing.speculative.box', $guidance->boxLabelKey);
        self::assertSame('tax_filing.speculative.codes', $guidance->codeLabelKey);
        self::assertNotNull($guidance->amount);
        // The user enters the gain in EUR; the SPF runs the 33% on its side.
        self::assertSame('1000', $guidance->amount->amount);
    }

    public function testSpeculativeOnALossIsZeroNotNegative(): void
    {
        $guidance = (new BelgianBoxMapper())->guide(Regime::Speculative, Money::of('-500', 'EUR'), 2024);

        self::assertNotNull($guidance->amount);
        self::assertSame('0', $guidance->amount->amount);
    }

    public function testProfessionalIsIntentionallyUnderspecified(): void
    {
        $guidance = (new BelgianBoxMapper())->guide(Regime::Professional, Money::of('1000', 'EUR'), 2024);

        self::assertNull($guidance->boxLabelKey);
        self::assertNull($guidance->codeLabelKey);
        self::assertNull($guidance->amount);
        self::assertSame('tax_filing.professional.note', $guidance->noteKey);
    }

    public function testFallsBackToAnUnsupportedYearNoteForOlderYears(): void
    {
        $guidance = (new BelgianBoxMapper())->guide(Regime::Speculative, Money::of('1000', 'EUR'), 2023);

        self::assertNull($guidance->boxLabelKey);
        self::assertSame('tax_filing.unsupported_year.note', $guidance->noteKey);
    }
}
