<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Domain\Event;

use DateTimeImmutable;
use Koersa\Portfolio\Domain\Event\TransactionAmended;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;

final class TransactionAmendedTest extends TestCase
{
    public function testPayloadRoundTripPreservesEveryField(): void
    {
        $event = new TransactionAmended(
            Uuid::generate(),
            Uuid::generate(),
            'ETH',
            Side::Sell,
            '2',
            '3000',
            '5',
            new DateTimeImmutable('2026-05-25T10:00:00+00:00'),
        );

        $restored = TransactionAmended::fromPayload($event->toPayload());

        self::assertTrue($restored->transactionId->equals($event->transactionId));
        self::assertTrue($restored->organizationId->equals($event->organizationId));
        self::assertSame('ETH', $restored->asset);
        self::assertSame(Side::Sell, $restored->side);
        self::assertSame('2', $restored->quantity);
        self::assertSame('3000', $restored->price);
        self::assertSame('5', $restored->fee);
        self::assertEquals($event->occurredAt, $restored->occurredAt);
    }
}
