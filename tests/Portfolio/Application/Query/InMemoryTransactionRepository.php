<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Application\Query;

use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Shared\Domain\Uuid;

final class InMemoryTransactionRepository implements TransactionRepository
{
    /** @var list<Transaction> */
    private array $transactions;

    /** @param list<Transaction> $transactions */
    public function __construct(array $transactions)
    {
        $this->transactions = $transactions;
    }

    public function save(Transaction $transaction): void
    {
        $this->transactions[] = $transaction;
    }

    public function remove(Uuid $transactionId): void
    {
        $this->transactions = array_values(array_filter(
            $this->transactions,
            static fn (Transaction $t): bool => !$t->id->equals($transactionId),
        ));
    }

    public function find(Uuid $transactionId): ?Transaction
    {
        foreach ($this->transactions as $transaction) {
            if ($transaction->id->equals($transactionId)) {
                return $transaction;
            }
        }

        return null;
    }

    public function forOrganization(Uuid $organizationId): array
    {
        $matching = array_values(array_filter(
            $this->transactions,
            static fn (Transaction $t): bool => $t->organizationId->equals($organizationId),
        ));

        // contract: most recent first
        usort($matching, static fn (Transaction $a, Transaction $b): int => $b->occurredAt <=> $a->occurredAt);

        return $matching;
    }
}
