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
        private bool $isAdmin = false,
        private bool $isPaid = false,
    ) {
    }

    public static function register(Uuid $id, Email $email, string $passwordHash, DateTimeImmutable $registeredAt): self
    {
        return new self($id, $email, $passwordHash, $registeredAt);
    }

    public static function reconstitute(Uuid $id, Email $email, string $passwordHash, DateTimeImmutable $registeredAt, bool $isAdmin = false, bool $isPaid = false): self
    {
        return new self($id, $email, $passwordHash, $registeredAt, $isAdmin, $isPaid);
    }

    public function changeEmail(Email $email): void
    {
        $this->email = $email;
    }

    public function changePassword(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function promoteToAdmin(): void
    {
        $this->isAdmin = true;
    }

    public function demoteFromAdmin(): void
    {
        $this->isAdmin = false;
    }

    public function promoteToPaid(): void
    {
        $this->isPaid = true;
    }

    public function demoteFromPaid(): void
    {
        $this->isPaid = false;
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

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function isPaid(): bool
    {
        return $this->isPaid;
    }
}
