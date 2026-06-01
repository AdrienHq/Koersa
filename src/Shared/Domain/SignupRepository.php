<?php

declare(strict_types=1);

namespace Koersa\Shared\Domain;

interface SignupRepository
{
    public function save(Signup $signup): void;

    public function existsByEmail(string $email): bool;

    public function count(): int;

    /** @return list<Signup> most recent first */
    public function recent(int $limit = 50): array;
}
