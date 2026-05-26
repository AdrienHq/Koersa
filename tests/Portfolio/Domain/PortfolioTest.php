<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Koersa\Portfolio\Domain\Event\TransactionAmended;
use Koersa\Portfolio\Domain\Event\TransactionRecorded;
use Koersa\Portfolio\Domain\Event\TransactionRemoved;
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

    public function testAmendingAKnownTradeEmitsAnAmendedEvent(): void
    {
        $organizationId = Uuid::generate();
        $transactionId = Uuid::generate();
        $portfolio = Portfolios::empty($organizationId);
        $portfolio->recordTransaction($transactionId, $organizationId, 'BTC', Side::Buy, '1', '100', '0', new DateTimeImmutable());

        $portfolio->amendTransaction($transactionId, $organizationId, ' eth ', Side::Sell, '2', '200', '1', new DateTimeImmutable());

        $events = $portfolio->releaseEvents();
        self::assertCount(2, $events);

        $amended = $events[1];
        self::assertInstanceOf(TransactionAmended::class, $amended);
        self::assertTrue($amended->transactionId->equals($transactionId));
        self::assertSame('ETH', $amended->asset);
        self::assertSame(Side::Sell, $amended->side);
        self::assertSame('2', $amended->quantity);
    }

    public function testRemovingAKnownTradeEmitsARemovedEvent(): void
    {
        $organizationId = Uuid::generate();
        $transactionId = Uuid::generate();
        $portfolio = Portfolios::empty($organizationId);
        $portfolio->recordTransaction($transactionId, $organizationId, 'BTC', Side::Buy, '1', '100', '0', new DateTimeImmutable());

        $portfolio->removeTransaction($transactionId);

        $events = $portfolio->releaseEvents();
        self::assertCount(2, $events);

        $removed = $events[1];
        self::assertInstanceOf(TransactionRemoved::class, $removed);
        self::assertTrue($removed->transactionId->equals($transactionId));
    }

    public function testCannotAmendAnUnknownTransaction(): void
    {
        $organizationId = Uuid::generate();

        $this->expectException(InvalidArgumentException::class);
        Portfolios::empty($organizationId)->amendTransaction(
            Uuid::generate(),
            $organizationId,
            'BTC',
            Side::Buy,
            '1',
            '100',
            '0',
            new DateTimeImmutable(),
        );
    }

    public function testCannotRemoveAnUnknownTransaction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Portfolios::empty(Uuid::generate())->removeTransaction(Uuid::generate());
    }

    public function testCannotAmendARemovedTransaction(): void
    {
        $organizationId = Uuid::generate();
        $transactionId = Uuid::generate();
        $portfolio = Portfolios::empty($organizationId);
        $portfolio->recordTransaction($transactionId, $organizationId, 'BTC', Side::Buy, '1', '100', '0', new DateTimeImmutable());
        $portfolio->removeTransaction($transactionId);

        $this->expectException(InvalidArgumentException::class);
        $portfolio->amendTransaction($transactionId, $organizationId, 'BTC', Side::Buy, '2', '100', '0', new DateTimeImmutable());
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
