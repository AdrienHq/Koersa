<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Domain;

use DateTimeImmutable;
use Koersa\IAM\Domain\User;
use Koersa\IAM\Domain\ValueObject\Email;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testRegisterExposesItsState(): void
    {
        $id = Uuid::generate();
        $registeredAt = new DateTimeImmutable('2026-05-24 10:00:00');

        $user = User::register($id, new Email('jane@example.com'), 'hash', $registeredAt);

        self::assertTrue($user->id()->equals($id));
        self::assertSame('jane@example.com', (string) $user->email());
        self::assertSame('hash', $user->passwordHash());
        self::assertEquals($registeredAt, $user->registeredAt());
    }

    public function testChangeEmail(): void
    {
        $user = $this->aUser();

        $user->changeEmail(new Email('new@example.com'));

        self::assertSame('new@example.com', (string) $user->email());
    }

    public function testChangePassword(): void
    {
        $user = $this->aUser();

        $user->changePassword('new-hash');

        self::assertSame('new-hash', $user->passwordHash());
    }

    private function aUser(): User
    {
        return User::register(Uuid::generate(), new Email('jane@example.com'), 'hash', new DateTimeImmutable());
    }
}
