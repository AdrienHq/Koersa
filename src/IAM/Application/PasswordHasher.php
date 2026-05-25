<?php

declare(strict_types=1);

namespace Koersa\IAM\Application;

/**
 * Port for turning a plain password into a stored hash. Implemented in the
 * infrastructure layer so the use case stays free of the security framework.
 */
interface PasswordHasher
{
    public function hash(string $plainPassword): string;
}
