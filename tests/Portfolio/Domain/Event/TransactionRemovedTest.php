<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Domain\Event;

use Koersa\Portfolio\Domain\Event\TransactionRemoved;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;

final class TransactionRemovedTest extends TestCase
{
    public function testPayloadRoundTripPreservesTheTransactionId(): void
    {
        $event = new TransactionRemoved(Uuid::generate());

        $restored = TransactionRemoved::fromPayload($event->toPayload());

        self::assertTrue($restored->transactionId->equals($event->transactionId));
    }
}
