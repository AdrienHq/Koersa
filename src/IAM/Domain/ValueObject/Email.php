<?php

declare(strict_types=1);

namespace Koersa\IAM\Domain\ValueObject;

use const FILTER_VALIDATE_EMAIL;

use InvalidArgumentException;
use Stringable;

final readonly class Email implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        if (false === filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a valid email address.', $value));
        }

        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
