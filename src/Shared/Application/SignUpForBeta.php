<?php

declare(strict_types=1);

namespace Koersa\Shared\Application;

final readonly class SignUpForBeta
{
    public function __construct(
        public string $email,
        public string $locale,
    ) {
    }
}
