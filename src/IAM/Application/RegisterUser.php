<?php

declare(strict_types=1);

namespace Koersa\IAM\Application;

final readonly class RegisterUser
{
    public function __construct(
        public string $email,
        public string $plainPassword,
        public string $organizationName,
    ) {
    }
}
