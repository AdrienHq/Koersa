<?php

declare(strict_types=1);

namespace Koersa\IAM\Application;

interface PasswordHasher
{
    public function hash(string $plainPassword): string;
}
