<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Application;

use DateTimeImmutable;
use Koersa\IAM\Application\EmailAlreadyInUse;
use Koersa\IAM\Application\PasswordHasher;
use Koersa\IAM\Application\RegisterUser;
use Koersa\IAM\Application\RegisterUserHandler;
use Koersa\IAM\Domain\Membership;
use Koersa\IAM\Domain\MembershipRepository;
use Koersa\IAM\Domain\Organization;
use Koersa\IAM\Domain\OrganizationRepository;
use Koersa\IAM\Domain\User;
use Koersa\IAM\Domain\UserRepository;
use Koersa\IAM\Domain\ValueObject\Email;
use Koersa\IAM\Domain\ValueObject\Role;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class RegisterUserHandlerTest extends TestCase
{
    public function testRegistersTheUserOrganizationAndAnOwnerMembership(): void
    {
        $users = $this->createMock(UserRepository::class);
        $organizations = $this->createMock(OrganizationRepository::class);
        $memberships = $this->createMock(MembershipRepository::class);

        $users->method('byEmail')->willReturn(null);
        $users->expects(self::once())->method('save')->with(self::callback(
            static fn (User $user): bool => 'jane@example.com' === (string) $user->email()
                && 'hashed-password' === $user->passwordHash(),
        ));
        $organizations->expects(self::once())->method('save')->with(self::callback(
            static fn (Organization $organization): bool => 'Acme Corp' === $organization->name(),
        ));
        $memberships->expects(self::once())->method('save')->with(self::callback(
            static fn (Membership $membership): bool => Role::Owner === $membership->role(),
        ));

        $hasher = $this->createStub(PasswordHasher::class);
        $hasher->method('hash')->willReturn('hashed-password');

        $handler = new RegisterUserHandler($users, $organizations, $memberships, $hasher, new MockClock());
        $handler(new RegisterUser('jane@example.com', 'secret-password', 'Acme Corp'));
    }

    public function testDefaultsTheOrganizationNameWhenBlank(): void
    {
        $users = $this->createMock(UserRepository::class);
        $users->method('byEmail')->willReturn(null);
        $users->expects(self::once())->method('save');

        $organizations = $this->createMock(OrganizationRepository::class);
        $organizations->expects(self::once())->method('save')->with(self::callback(
            static fn (Organization $organization): bool => 'Personal' === $organization->name(),
        ));
        $memberships = $this->createMock(MembershipRepository::class);
        $memberships->expects(self::once())->method('save');

        $hasher = $this->createStub(PasswordHasher::class);
        $hasher->method('hash')->willReturn('hashed-password');

        $handler = new RegisterUserHandler($users, $organizations, $memberships, $hasher, new MockClock());
        $handler(new RegisterUser('jane@example.com', 'secret-password', '   '));
    }

    public function testRejectsAnAlreadyRegisteredEmail(): void
    {
        $users = $this->createMock(UserRepository::class);
        $users->method('byEmail')->willReturn(
            User::register(Uuid::generate(), new Email('jane@example.com'), 'hash', new DateTimeImmutable()),
        );
        $users->expects(self::never())->method('save');

        $organizations = $this->createMock(OrganizationRepository::class);
        $organizations->expects(self::never())->method('save');
        $memberships = $this->createMock(MembershipRepository::class);
        $memberships->expects(self::never())->method('save');

        $handler = new RegisterUserHandler(
            $users,
            $organizations,
            $memberships,
            $this->createStub(PasswordHasher::class),
            new MockClock(),
        );

        $this->expectException(EmailAlreadyInUse::class);
        $handler(new RegisterUser('jane@example.com', 'secret-password', 'Acme Corp'));
    }
}
