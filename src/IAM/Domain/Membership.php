<?php

declare(strict_types=1);

namespace Koersa\IAM\Domain;

use DateTimeImmutable;
use Koersa\IAM\Domain\ValueObject\Role;
use Koersa\Shared\Domain\Uuid;

/**
 * Associates a user with an organization and the role they hold there.
 * References the user and organization by identity, not by object reference,
 * since each is its own aggregate.
 */
final class Membership
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $userId,
        private readonly Uuid $organizationId,
        private Role $role,
        private readonly DateTimeImmutable $joinedAt,
    ) {
    }

    public static function create(Uuid $id, Uuid $userId, Uuid $organizationId, Role $role, DateTimeImmutable $joinedAt): self
    {
        return new self($id, $userId, $organizationId, $role, $joinedAt);
    }

    /**
     * Rebuild a membership from stored state. Used by the persistence mapper
     * only.
     */
    public static function reconstitute(Uuid $id, Uuid $userId, Uuid $organizationId, Role $role, DateTimeImmutable $joinedAt): self
    {
        return new self($id, $userId, $organizationId, $role, $joinedAt);
    }

    public function changeRole(Role $role): void
    {
        $this->role = $role;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function organizationId(): Uuid
    {
        return $this->organizationId;
    }

    public function role(): Role
    {
        return $this->role;
    }

    public function joinedAt(): DateTimeImmutable
    {
        return $this->joinedAt;
    }
}
