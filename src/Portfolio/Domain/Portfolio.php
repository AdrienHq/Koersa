<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Domain;

use DateTimeImmutable;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use InvalidArgumentException;
use Koersa\Portfolio\Domain\Event\TransactionAmended;
use Koersa\Portfolio\Domain\Event\TransactionRecorded;
use Koersa\Portfolio\Domain\Event\TransactionRemoved;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;

/**
 * The event-sourced aggregate for a single organization's portfolio. Trades are
 * recorded, amended, and removed as events; the holdings and transactions read
 * models are projected from the stream. Invariants live here, not on the read
 * models. See ADR 0002.
 *
 * @implements AggregateRoot<PortfolioId>
 */
final class Portfolio implements AggregateRoot
{
    /** @use AggregateRootBehaviour<PortfolioId> */
    use AggregateRootBehaviour;

    /**
     * Ids of the trades currently in the portfolio, rebuilt by replaying events.
     * Amend/remove are only valid against one of these.
     *
     * @var array<string, true>
     */
    private array $transactions = [];

    public function recordTransaction(
        Uuid $transactionId,
        Uuid $organizationId,
        string $asset,
        Side $side,
        string $quantity,
        string $price,
        string $fee,
        DateTimeImmutable $occurredAt,
    ): void {
        $asset = $this->validateTrade($asset, $quantity, $price, $fee);

        $this->recordThat(new TransactionRecorded($transactionId, $organizationId, $asset, $side, $quantity, $price, $fee, $occurredAt));
    }

    public function amendTransaction(
        Uuid $transactionId,
        Uuid $organizationId,
        string $asset,
        Side $side,
        string $quantity,
        string $price,
        string $fee,
        DateTimeImmutable $occurredAt,
    ): void {
        $this->guardKnownTransaction($transactionId);
        $asset = $this->validateTrade($asset, $quantity, $price, $fee);

        $this->recordThat(new TransactionAmended($transactionId, $organizationId, $asset, $side, $quantity, $price, $fee, $occurredAt));
    }

    public function removeTransaction(Uuid $transactionId): void
    {
        $this->guardKnownTransaction($transactionId);

        $this->recordThat(new TransactionRemoved($transactionId));
    }

    protected function applyTransactionRecorded(TransactionRecorded $event): void
    {
        $this->transactions[$event->transactionId->value] = true;
    }

    protected function applyTransactionAmended(TransactionAmended $event): void
    {
        // Amending changes a trade's values, not whether it exists; the new
        // values live in the projection, so there is no aggregate state to move.
    }

    protected function applyTransactionRemoved(TransactionRemoved $event): void
    {
        unset($this->transactions[$event->transactionId->value]);
    }

    /**
     * Validates a trade and returns the normalized asset symbol.
     */
    private function validateTrade(string $asset, string $quantity, string $price, string $fee): string
    {
        $asset = strtoupper(trim($asset));

        if (1 !== preg_match('/^[A-Z0-9]{1,12}$/', $asset)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a valid asset symbol.', $asset));
        }
        if (!is_numeric($quantity) || (float) $quantity <= 0.0) {
            throw new InvalidArgumentException('Quantity must be a positive number.');
        }
        if (!is_numeric($price) || (float) $price < 0.0) {
            throw new InvalidArgumentException('Price must be zero or a positive number.');
        }
        if (!is_numeric($fee) || (float) $fee < 0.0) {
            throw new InvalidArgumentException('Fee must be zero or a positive number.');
        }

        return $asset;
    }

    private function guardKnownTransaction(Uuid $transactionId): void
    {
        if (!isset($this->transactions[$transactionId->value])) {
            throw new InvalidArgumentException(\sprintf('Transaction "%s" is not part of this portfolio.', $transactionId->value));
        }
    }
}
