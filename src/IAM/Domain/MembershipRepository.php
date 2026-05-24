<?php

declare(strict_types=1);

namespace Koersa\IAM\Domain;

use Koersa\Shared\Domain\Uuid;

interface MembershipRepository
{
    public function save(Membership $membership): void;

    public function byId(Uuid $id): ?Membership;

    /**
     * @return list<Membership>
     */
    public function forUser(Uuid $userId): array;
}
