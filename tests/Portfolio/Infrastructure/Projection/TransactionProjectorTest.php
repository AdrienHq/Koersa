<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Infrastructure\Projection;

use DateTimeImmutable;
use EventSauce\EventSourcing\Message;
use Koersa\Portfolio\Domain\Event\TransactionAmended;
use Koersa\Portfolio\Domain\Event\TransactionRecorded;
use Koersa\Portfolio\Domain\Event\TransactionRemoved;
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

        $event = new TransactionRecorded(Uuid::generate(), $organizationId, 'BTC', Side::Buy, '0.5', '40000', '10', new DateTimeImmutable());

        (new TransactionProjector($transactions))->handle(new Message($event));
    }

    public function testProjectsAnAmendmentByRewritingTheRow(): void
    {
        $transactionId = Uuid::generate();
        $transactions = $this->createMock(TransactionRepository::class);
        $transactions->expects(self::once())->method('save')->with(self::callback(
            static fn (Transaction $transaction): bool => $transaction->id->equals($transactionId)
                && 'ETH' === $transaction->asset
                && '2' === $transaction->quantity,
        ));

        $event = new TransactionAmended($transactionId, Uuid::generate(), 'ETH', Side::Sell, '2', '3000', '5', new DateTimeImmutable());

        (new TransactionProjector($transactions))->handle(new Message($event));
    }

    public function testProjectsARemovalByDeletingTheRow(): void
    {
        $transactionId = Uuid::generate();
        $transactions = $this->createMock(TransactionRepository::class);
        $transactions->expects(self::never())->method('save');
        $transactions->expects(self::once())->method('remove')->with(self::callback(
            static fn (Uuid $id): bool => $id->equals($transactionId),
        ));

        (new TransactionProjector($transactions))->handle(new Message(new TransactionRemoved($transactionId)));
    }

    public function testIgnoresEventsItDoesNotProject(): void
    {
        $transactions = $this->createMock(TransactionRepository::class);
        $transactions->expects(self::never())->method('save');
        $transactions->expects(self::never())->method('remove');

        (new TransactionProjector($transactions))->handle(new Message(new stdClass()));
    }
}
