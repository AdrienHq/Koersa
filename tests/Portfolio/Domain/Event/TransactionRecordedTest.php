<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Domain\Event;

use DateTimeImmutable;
use Koersa\Portfolio\Domain\Event\TransactionRecorded;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;

final class TransactionRecordedTest extends TestCase
{
    public function testPayloadRoundTripPreservesEveryField(): void
    {
        $event = new TransactionRecorded(
            Uuid::generate(),
            Uuid::generate(),
            'BTC',
            Side::Buy,
            '0.5',
            '40000',
            '10',
            new DateTimeImmutable('2026-05-25T10:00:00+00:00'),
        );

        $restored = TransactionRecorded::fromPayload($event->toPayload());

        self::assertTrue($restored->transactionId->equals($event->transactionId));
        self::assertTrue($restored->organizationId->equals($event->organizationId));
        self::assertSame('BTC', $restored->asset);
        self::assertSame(Side::Buy, $restored->side);
        self::assertSame('0.5', $restored->quantity);
        self::assertSame('40000', $restored->price);
        self::assertSame('10', $restored->fee);
        self::assertEquals($event->occurredAt, $restored->occurredAt);
        self::assertSame('manual', $restored->source);
        self::assertNull($restored->externalId);
    }

    public function testPayloadRoundTripPreservesProvenance(): void
    {
        $event = new TransactionRecorded(
            Uuid::generate(),
            Uuid::generate(),
            'BTC',
            Side::Buy,
            '0.5',
            '40000',
            '10',
            new DateTimeImmutable('2026-05-25T10:00:00+00:00'),
            'kraken',
            'LEDGER-123',
        );

        $restored = TransactionRecorded::fromPayload($event->toPayload());

        self::assertSame('kraken', $restored->source);
        self::assertSame('LEDGER-123', $restored->externalId);
    }

    public function testPayloadRoundTripPreservesCurrencies(): void
    {
        $event = new TransactionRecorded(
            Uuid::generate(),
            Uuid::generate(),
            'BTC',
            Side::Buy,
            '0.5',
            '40000',
            '10',
            new DateTimeImmutable('2026-05-25T10:00:00+00:00'),
            priceCurrency: 'USD',
            feeCurrency: 'USD',
        );

        $restored = TransactionRecorded::fromPayload($event->toPayload());

        self::assertSame('USD', $restored->priceCurrency);
        self::assertSame('USD', $restored->feeCurrency);
    }

    public function testLegacyPayloadWithoutCurrenciesDefaultsToEur(): void
    {
        $legacy = [
            'transactionId' => Uuid::generate()->value,
            'organizationId' => Uuid::generate()->value,
            'asset' => 'BTC',
            'side' => 'buy',
            'quantity' => '0.5',
            'price' => '40000',
            'fee' => '10',
            'occurredAt' => '2025-01-01T00:00:00+00:00',
        ];

        $event = TransactionRecorded::fromPayload($legacy);

        self::assertSame('EUR', $event->priceCurrency);
        self::assertSame('EUR', $event->feeCurrency);
    }
}
