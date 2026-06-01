<?php

declare(strict_types=1);

namespace Koersa\IAM\Domain;

use Koersa\IAM\Domain\ValueObject\Email;
use Koersa\Shared\Domain\Uuid;

interface UserRepository
{
    public function save(User $user): void;

    public function byId(Uuid $id): ?User;

    public function byEmail(Email $email): ?User;

    public function count(): int;

    /** @return list<User> most recent first */
    public function recent(int $limit = 50): array;
}
