<?php

declare(strict_types=1);

namespace Koersa\IAM\Domain;

use DateTimeImmutable;
use Koersa\IAM\Domain\ValueObject\Email;
use Koersa\Shared\Domain\Uuid;

final class User
{
    private function __construct(
        private readonly Uuid $id,
        private Email $email,
        private string $passwordHash,
        private readonly DateTimeImmutable $registeredAt,
    ) {
    }

    public static function register(Uuid $id, Email $email, string $passwordHash, DateTimeImmutable $registeredAt): self
    {
        return new self($id, $email, $passwordHash, $registeredAt);
    }

    public static function reconstitute(Uuid $id, Email $email, string $passwordHash, DateTimeImmutable $registeredAt): self
    {
        return new self($id, $email, $passwordHash, $registeredAt);
    }

    public function changeEmail(Email $email): void
    {
        $this->email = $email;
    }

    public function changePassword(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function registeredAt(): DateTimeImmutable
    {
        return $this->registeredAt;
    }
}
