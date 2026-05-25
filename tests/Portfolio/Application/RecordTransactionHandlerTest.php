<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Application;

use DateTimeImmutable;
use Koersa\Portfolio\Application\RecordTransaction;
use Koersa\Portfolio\Application\RecordTransactionHandler;
use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;

final class RecordTransactionHandlerTest extends TestCase
{
    public function testSavesTheRecordedTransaction(): void
    {
        $organizationId = Uuid::generate();
        $transactions = $this->createMock(TransactionRepository::class);
        $transactions->expects(self::once())->method('save')->with(self::callback(
            static fn (Transaction $transaction): bool => $transaction->organizationId->equals($organizationId)
                && 'ETH' === $transaction->asset
                && Side::Sell === $transaction->side
                && '2' === $transaction->quantity,
        ));

        $handler = new RecordTransactionHandler($transactions);
        $handler(new RecordTransaction($organizationId, 'ETH', Side::Sell, '2', '3000', '5', new DateTimeImmutable()));
    }
}
