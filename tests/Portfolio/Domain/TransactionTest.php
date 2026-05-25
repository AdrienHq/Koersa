<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;

final class TransactionTest extends TestCase
{
    public function testRecordsATradeAndNormalizesTheAsset(): void
    {
        $transaction = Transaction::record(
            Uuid::generate(),
            Uuid::generate(),
            ' btc ',
            Side::Buy,
            '0.5',
            '40000',
            '10',
            new DateTimeImmutable('2026-05-25 10:00'),
        );

        self::assertSame('BTC', $transaction->asset);
        self::assertSame(Side::Buy, $transaction->side);
        self::assertSame('0.5', $transaction->quantity);
    }

    public function testRejectsAnInvalidAssetSymbol(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->record(asset: 'BT-C');
    }

    public function testRejectsANonPositiveQuantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->record(quantity: '0');
    }

    public function testRejectsANegativePrice(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->record(price: '-1');
    }

    private function record(string $asset = 'BTC', string $quantity = '1', string $price = '100'): Transaction
    {
        return Transaction::record(
            Uuid::generate(),
            Uuid::generate(),
            $asset,
            Side::Buy,
            $quantity,
            $price,
            '0',
            new DateTimeImmutable(),
        );
    }
}
