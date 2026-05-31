<?php

declare(strict_types=1);

namespace Koersa\Shared\Domain;

use DateTimeImmutable;

use const FILTER_VALIDATE_EMAIL;

use InvalidArgumentException;

// Beta-access lead. In Shared because it is too small to justify its own context.
final readonly class Signup
{
    private const array LOCALES = ['fr', 'nl', 'en'];

    private function __construct(
        public Uuid $id,
        public string $email,
        public string $locale,
        public DateTimeImmutable $signedUpAt,
    ) {
    }

    public static function register(Uuid $id, string $email, string $locale, DateTimeImmutable $signedUpAt): self
    {
        $email = strtolower(trim($email));

        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a valid email address.', $email));
        }
        if (!\in_array($locale, self::LOCALES, true)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a supported locale.', $locale));
        }

        return new self($id, $email, $locale, $signedUpAt);
    }

    public static function reconstitute(Uuid $id, string $email, string $locale, DateTimeImmutable $signedUpAt): self
    {
        return new self($id, $email, $locale, $signedUpAt);
    }
}
