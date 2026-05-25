<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Domain;

use Koersa\Shared\Domain\Uuid;

interface TransactionRepository
{
    public function save(Transaction $transaction): void;

    /**
     * Transactions for an organization, most recent first.
     *
     * @return list<Transaction>
     */
    public function forOrganization(Uuid $organizationId): array;
}
