<?php

declare(strict_types=1);

namespace Koersa\Shared\Domain;

interface SignupRepository
{
    public function save(Signup $signup): void;

    public function existsByEmail(string $email): bool;
}
