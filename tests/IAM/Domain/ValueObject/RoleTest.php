<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Domain\ValueObject;

use Koersa\IAM\Domain\ValueObject\Role;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    public function testOwnerAndAdminMayManageMembers(): void
    {
        self::assertTrue(Role::Owner->canManageMembers());
        self::assertTrue(Role::Admin->canManageMembers());
    }

    public function testMemberMayNotManageMembers(): void
    {
        self::assertFalse(Role::Member->canManageMembers());
    }
}
