<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;

/**
 * A single recorded trade. Immutable: corrections are new transactions, never
 * mutations. Amounts are kept as decimal strings to avoid float drift.
 *
 * This is a plain Doctrine-backed aggregate for Iteration 1; Iteration 2
 * migrates the Portfolio context to event sourcing.
 */
final readonly class Transaction
{
    private function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public string $asset,
        public Side $side,
        public string $quantity,
        public string $price,
        public string $fee,
        public DateTimeImmutable $occurredAt,
    ) {
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
    }

    public static function record(
        Uuid $id,
        Uuid $organizationId,
        string $asset,
        Side $side,
        string $quantity,
        string $price,
        string $fee,
        DateTimeImmutable $occurredAt,
    ): self {
        return new self($id, $organizationId, strtoupper(trim($asset)), $side, $quantity, $price, $fee, $occurredAt);
    }

    /**
     * Rebuild from stored state (persistence mapper only).
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        string $asset,
        Side $side,
        string $quantity,
        string $price,
        string $fee,
        DateTimeImmutable $occurredAt,
    ): self {
        return new self($id, $organizationId, $asset, $side, $quantity, $price, $fee, $occurredAt);
    }
}
