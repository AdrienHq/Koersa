<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Infrastructure\Projection;

use DateTimeImmutable;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use Koersa\Portfolio\Domain\Event\TransactionAmended;
use Koersa\Portfolio\Domain\Event\TransactionRecorded;
use Koersa\Portfolio\Domain\Event\TransactionRemoved;
use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;

/**
 * Projects the Portfolio event stream into the transactions read model that the
 * dashboard and the holdings query read from. Keyed by transaction id, so
 * replaying the whole stream during a rebuild reproduces the same rows.
 */
final class TransactionProjector implements MessageConsumer
{
    public function __construct(private readonly TransactionRepository $transactions)
    {
    }

    public function handle(Message $message): void
    {
        $event = $message->payload();

        if ($event instanceof TransactionRecorded) {
            $this->project($event->transactionId, $event->organizationId, $event->asset, $event->side, $event->quantity, $event->price, $event->fee, $event->occurredAt);

            return;
        }

        if ($event instanceof TransactionAmended) {
            $this->project($event->transactionId, $event->organizationId, $event->asset, $event->side, $event->quantity, $event->price, $event->fee, $event->occurredAt);

            return;
        }

        if ($event instanceof TransactionRemoved) {
            $this->transactions->remove($event->transactionId);
        }
    }

    private function project(
        Uuid $transactionId,
        Uuid $organizationId,
        string $asset,
        Side $side,
        string $quantity,
        string $price,
        string $fee,
        DateTimeImmutable $occurredAt,
    ): void {
        $this->transactions->save(Transaction::reconstitute(
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
}
