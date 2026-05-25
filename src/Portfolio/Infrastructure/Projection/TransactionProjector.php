<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Infrastructure\Projection;

use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use Koersa\Portfolio\Domain\Event\TransactionRecorded;
use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\TransactionRepository;

/**
 * Projects TransactionRecorded events into the transactions read model that the
 * dashboard and the holdings query read from. Keyed by transaction id, so
 * replaying the whole stream during a rebuild simply rewrites the same rows.
 */
final class TransactionProjector implements MessageConsumer
{
    public function __construct(private readonly TransactionRepository $transactions)
    {
    }

    public function handle(Message $message): void
    {
        $event = $message->payload();

        if (!$event instanceof TransactionRecorded) {
            return;
        }

        $this->transactions->save(Transaction::reconstitute(
            $event->transactionId,
            $event->organizationId,
            $event->asset,
            $event->side,
            $event->quantity,
            $event->price,
            $event->fee,
            $event->occurredAt,
        ));
    }
}
