<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Domain;

use Koersa\Shared\Domain\Uuid;

interface TransactionRepository
{
    public function save(Transaction $transaction): void;

    public function remove(Uuid $transactionId): void;

    public function find(Uuid $transactionId): ?Transaction;

    /** @return list<Transaction> most recent first */
    public function forOrganization(Uuid $organizationId): array;
}
