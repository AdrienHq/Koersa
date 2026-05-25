<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Koersa\Portfolio\Domain\Event\TransactionRecorded;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;
use Koersa\Tests\Support\Portfolios;
use PHPUnit\Framework\TestCase;

final class PortfolioTest extends TestCase
{
    public function testRecordingATradeEmitsAnEventWithANormalizedAsset(): void
    {
        $organizationId = Uuid::generate();
        $portfolio = Portfolios::empty($organizationId);

        $portfolio->recordTransaction(
            Uuid::generate(),
            $organizationId,
            ' btc ',
            Side::Buy,
            '0.5',
            '40000',
            '10',
            new DateTimeImmutable('2026-05-25 10:00'),
        );

        $events = $portfolio->releaseEvents();
        self::assertCount(1, $events);

        $event = $events[0];
        self::assertInstanceOf(TransactionRecorded::class, $event);
        self::assertSame('BTC', $event->asset);
        self::assertSame(Side::Buy, $event->side);
        self::assertSame('0.5', $event->quantity);
        self::assertSame(1, $portfolio->aggregateRootVersion());
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

    public function testRejectsANegativeFee(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->record(fee: '-1');
    }

    private function record(string $asset = 'BTC', string $quantity = '1', string $price = '100', string $fee = '0'): void
    {
        $organizationId = Uuid::generate();

        Portfolios::empty($organizationId)->recordTransaction(
            Uuid::generate(),
            $organizationId,
            $asset,
            Side::Buy,
            $quantity,
            $price,
            $fee,
            new DateTimeImmutable(),
        );
    }
}
