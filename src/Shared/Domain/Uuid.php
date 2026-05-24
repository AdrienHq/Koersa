<?php

declare(strict_types=1);

namespace Koersa\Shared\Domain;

use InvalidArgumentException;
use Stringable;

/**
 * RFC 4122 version-4 identifier. Generated without an external library so the
 * domain layer stays dependency-free.
 */
final readonly class Uuid implements Stringable
{
    private const string PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    private function __construct(public string $value)
    {
        if (1 !== preg_match(self::PATTERN, $value)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a valid UUID.', $value));
        }
    }

    public static function generate(): self
    {
        $bytes = random_bytes(16);
        $bytes[6] = \chr((\ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = \chr((\ord($bytes[8]) & 0x3F) | 0x80);

        $hex = bin2hex($bytes);

        return new self(\sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        ));
    }

    public static function fromString(string $value): self
    {
        return new self(strtolower($value));
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
