<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application;

use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Shared\Domain\Uuid;

final class RecordTransactionHandler
{
    public function __construct(private readonly TransactionRepository $transactions)
    {
    }

    public function __invoke(RecordTransaction $command): void
    {
        $this->transactions->save(Transaction::record(
            Uuid::generate(),
            $command->organizationId,
            $command->asset,
            $command->side,
            $command->quantity,
            $command->price,
            $command->fee,
            $command->occurredAt,
        ));
    }
}
