<?php

declare(strict_types=1);

namespace Koersa\IAM\Domain\ValueObject;

enum Role: string
{
    case Owner = 'OWNER';
    case Admin = 'ADMIN';
    case Member = 'MEMBER';

    /**
     * Whether this role may add, remove, or re-role other members of its
     * organization. Used by authorization voters (Iteration 4).
     */
    public function canManageMembers(): bool
    {
        return self::Owner === $this || self::Admin === $this;
    }
}
