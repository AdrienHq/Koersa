<?php

declare(strict_types=1);

namespace Koersa\IAM\Domain\ValueObject;

enum Role: string
{
    case Owner = 'OWNER';
    case Admin = 'ADMIN';
    case Member = 'MEMBER';

    public function canManageMembers(): bool
    {
        return self::Owner === $this || self::Admin === $this;
    }
}
