<?php

declare(strict_types=1);

namespace Koersa\Shared\Application;

/**
 * Command: register interest in the Koersa beta from the landing page.
 */
final readonly class SignUpForBeta
{
    public function __construct(
        public string $email,
        public string $locale,
    ) {
    }
}
