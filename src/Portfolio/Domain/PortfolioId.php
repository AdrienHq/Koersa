<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Domain;

use EventSauce\EventSourcing\AggregateRootId;
use Koersa\Shared\Domain\Uuid;

/**
 * Identifies a Portfolio aggregate. There is one portfolio per organization, so
 * the identifier is simply the organization's id — no extra mapping to keep.
 */
final readonly class PortfolioId implements AggregateRootId
{
    private function __construct(private Uuid $id)
    {
    }

    public static function forOrganization(Uuid $organizationId): self
    {
        return new self($organizationId);
    }

    public function toString(): string
    {
        $value = $this->id->value;
        \assert('' !== $value);

        return $value;
    }

    public static function fromString(string $aggregateRootId): static
    {
        return new self(Uuid::fromString($aggregateRootId));
    }
}
