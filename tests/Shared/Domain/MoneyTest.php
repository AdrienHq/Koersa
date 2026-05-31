<?php

declare(strict_types=1);

namespace Koersa\Tests\Shared\Domain;

use InvalidArgumentException;
use Koersa\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testOfNormalizesTrailingZerosAndUppercasesTheCurrency(): void
    {
        $money = Money::of('1.500', 'eur');

        self::assertSame('1.5', $money->amount);
        self::assertSame('EUR', $money->currency);
    }

    public function testZeroFactory(): void
    {
        $money = Money::zero('USD');

        self::assertTrue($money->isZero());
        self::assertSame('USD', $money->currency);
    }

    public function testRejectsAMalformedAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::of('not-a-number', 'EUR');
    }

    public function testRejectsAnUnsupportedCurrencyFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::of('1', 'E');
    }

    public function testAddSameCurrency(): void
    {
        $sum = Money::of('1.1', 'EUR')->add(Money::of('2.2', 'EUR'));

        self::assertSame('3.3', $sum->amount);
        self::assertSame('EUR', $sum->currency);
    }

    public function testAddRefusesCrossCurrency(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::of('1', 'EUR')->add(Money::of('1', 'USD'));
    }

    public function testSubtractCanGoNegative(): void
    {
        $diff = Money::of('1', 'EUR')->subtract(Money::of('3', 'EUR'));

        self::assertSame('-2', $diff->amount);
        self::assertTrue($diff->isNegative());
    }

    public function testMultiplyKeepsHighPrecision(): void
    {
        $product = Money::of('0.1', 'EUR')->multiply('0.2');

        // 0.1 * 0.2 = 0.02. With floats this would be 0.020000000000000004; we
        // need bcmath precision here precisely because tax math sees these.
        self::assertSame('0.02', $product->amount);
    }

    public function testConvertedToProducesADifferentCurrency(): void
    {
        $converted = Money::of('100', 'USD')->convertedTo('EUR', '0.92');

        self::assertSame('92', $converted->amount);
        self::assertSame('EUR', $converted->currency);
    }

    public function testEqualityIgnoresTrailingZerosAndCases(): void
    {
        self::assertTrue(Money::of('1.50', 'EUR')->equals(Money::of('1.5', 'eur')));
        self::assertFalse(Money::of('1.5', 'EUR')->equals(Money::of('1.5', 'USD')));
    }

    public function testToStringRendersAmountAndCurrency(): void
    {
        self::assertSame('1.5 EUR', (string) Money::of('1.5', 'EUR'));
    }
}
