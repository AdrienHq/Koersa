<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Infrastructure\Projection;

use DateTimeImmutable;
use EventSauce\EventSourcing\Message;
use Koersa\Portfolio\Domain\Event\TransactionRecorded;
use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Portfolio\Infrastructure\Projection\TransactionProjector;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;
use stdClass;

final class TransactionProjectorTest extends TestCase
{
    public function testProjectsARecordedTransactionIntoTheReadModel(): void
    {
        $organizationId = Uuid::generate();
        $transactions = $this->createMock(TransactionRepository::class);
        $transactions->expects(self::once())->method('save')->with(self::callback(
            static fn (Transaction $transaction): bool => 'BTC' === $transaction->asset
                && $transaction->organizationId->equals($organizationId)
                && '0.5' === $transaction->quantity,
        ));

        $event = new TransactionRecorded(
            Uuid::generate(),
            $organizationId,
            'BTC',
            Side::Buy,
            '0.5',
            '40000',
            '10',
            new DateTimeImmutable(),
        );

        (new TransactionProjector($transactions))->handle(new Message($event));
    }

    public function testIgnoresEventsItDoesNotProject(): void
    {
        $transactions = $this->createMock(TransactionRepository::class);
        $transactions->expects(self::never())->method('save');

        (new TransactionProjector($transactions))->handle(new Message(new stdClass()));
    }
}
