<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Domain\Event;

use EventSauce\EventSourcing\Serialization\SerializablePayload;
use Koersa\Shared\Domain\Uuid;

final readonly class TransactionRemoved implements SerializablePayload
{
    public function __construct(public Uuid $transactionId)
    {
    }

    /**
     * @return array<string, string>
     */
    public function toPayload(): array
    {
        return ['transactionId' => $this->transactionId->value];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): static
    {
        $transactionId = $payload['transactionId'] ?? null;
        \assert(\is_string($transactionId));

        return new self(Uuid::fromString($transactionId));
    }
}
