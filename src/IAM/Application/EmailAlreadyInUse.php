<?php

declare(strict_types=1);

namespace Koersa\IAM\Application;

use Koersa\IAM\Domain\ValueObject\Email;
use RuntimeException;

final class EmailAlreadyInUse extends RuntimeException
{
    public static function withEmail(Email $email): self
    {
        return new self(\sprintf('A user with email "%s" already exists.', $email));
    }
}
