<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Domain;

use DateTimeImmutable;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use InvalidArgumentException;
use Koersa\Portfolio\Domain\Event\TransactionRecorded;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;

/**
 * The event-sourced aggregate for a single organization's portfolio. Trades are
 * recorded as events; the holdings and transactions read models are projected
 * from the stream. Invariants live here, not on the read models. See ADR 0002.
 *
 * @implements AggregateRoot<PortfolioId>
 */
final class Portfolio implements AggregateRoot
{
    /** @use AggregateRootBehaviour<PortfolioId> */
    use AggregateRootBehaviour;

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

        $this->recordThat(new TransactionRecorded(
            $transactionId,
            $organizationId,
            $asset,
            $side,
            $quantity,
            $price,
            $fee,
            $occurredAt,
        ));
    }

    protected function applyTransactionRecorded(TransactionRecorded $event): void
    {
        // No cross-transaction invariant exists yet: in the MVP each trade
        // stands alone, so the aggregate keeps no rolling state. A rule such as
        // "cannot sell more than is held" would start tracking positions here.
    }
}
