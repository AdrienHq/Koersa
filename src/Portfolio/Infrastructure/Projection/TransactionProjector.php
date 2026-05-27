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

// Projects the event stream into the transactions read model; rebuildable.
final class TransactionProjector implements MessageConsumer
{
    public function __construct(private readonly TransactionRepository $transactions)
    {
    }

    public function handle(Message $message): void
    {
        $event = $message->payload();

        if ($event instanceof TransactionRecorded) {
            $this->project($event->transactionId, $event->organizationId, $event->asset, $event->side, $event->quantity, $event->price, $event->fee, $event->occurredAt, $event->source, $event->externalId);

            return;
        }

        if ($event instanceof TransactionAmended) {
            // provenance is set at creation, so keep it from the existing row
            $existing = $this->transactions->find($event->transactionId);
            $source = null !== $existing ? $existing->source : 'manual';
            $this->project($event->transactionId, $event->organizationId, $event->asset, $event->side, $event->quantity, $event->price, $event->fee, $event->occurredAt, $source, $existing?->externalId);

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
        string $source,
        ?string $externalId,
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
            $source,
            $externalId,
        ));
    }
}
