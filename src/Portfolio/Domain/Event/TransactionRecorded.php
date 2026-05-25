<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Domain\Event;

use DateTimeImmutable;
use EventSauce\EventSourcing\Serialization\SerializablePayload;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;

/**
 * Raised when a trade is recorded in a portfolio. This event is the source of
 * truth for the Portfolio context; the holdings and transactions read models
 * are projected from the stream of these. Amounts stay decimal strings.
 */
final readonly class TransactionRecorded implements SerializablePayload
{
    public function __construct(
        public Uuid $transactionId,
        public Uuid $organizationId,
        public string $asset,
        public Side $side,
        public string $quantity,
        public string $price,
        public string $fee,
        public DateTimeImmutable $occurredAt,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function toPayload(): array
    {
        return [
            'transactionId' => $this->transactionId->value,
            'organizationId' => $this->organizationId->value,
            'asset' => $this->asset,
            'side' => $this->side->value,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'fee' => $this->fee,
            'occurredAt' => $this->occurredAt->format(DateTimeImmutable::ATOM),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): static
    {
        return new self(
            Uuid::fromString(self::string($payload, 'transactionId')),
            Uuid::fromString(self::string($payload, 'organizationId')),
            self::string($payload, 'asset'),
            Side::from(self::string($payload, 'side')),
            self::string($payload, 'quantity'),
            self::string($payload, 'price'),
            self::string($payload, 'fee'),
            new DateTimeImmutable(self::string($payload, 'occurredAt')),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function string(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;
        \assert(\is_string($value));

        return $value;
    }
}
