<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Infrastructure\Security;

use DateTimeImmutable;
use Koersa\IAM\Domain\Membership;
use Koersa\IAM\Domain\MembershipRepository;
use Koersa\IAM\Domain\User;
use Koersa\IAM\Domain\UserRepository;
use Koersa\IAM\Domain\ValueObject\Email;
use Koersa\IAM\Domain\ValueObject\Role;
use Koersa\IAM\Infrastructure\Security\SecurityUser;
use Koersa\IAM\Infrastructure\Security\SecurityUserProvider;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityUserProviderTest extends TestCase
{
    public function testLoadsAUserWithItsOrganization(): void
    {
        $userId = Uuid::generate();
        $organizationId = Uuid::generate();
        $provider = $this->provider(
            User::register($userId, new Email('jane@example.com'), 'hashed', new DateTimeImmutable()),
            [Membership::create(Uuid::generate(), $userId, $organizationId, Role::Owner, new DateTimeImmutable())],
        );

        $securityUser = $provider->loadUserByIdentifier('jane@example.com');

        self::assertSame('jane@example.com', $securityUser->getUserIdentifier());
        self::assertSame('hashed', $securityUser->getPassword());
        self::assertTrue($securityUser->organizationId()->equals($organizationId));
    }

    public function testThrowsWhenTheUserIsNotFound(): void
    {
        $provider = $this->provider(null, []);

        $this->expectException(UserNotFoundException::class);
        $provider->loadUserByIdentifier('ghost@example.com');
    }

    public function testThrowsWhenTheUserHasNoOrganization(): void
    {
        $provider = $this->provider(
            User::register(Uuid::generate(), new Email('jane@example.com'), 'hashed', new DateTimeImmutable()),
            [],
        );

        $this->expectException(UserNotFoundException::class);
        $provider->loadUserByIdentifier('jane@example.com');
    }

    public function testThrowsWhenTheIdentifierIsNotAnEmail(): void
    {
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::never())->method('byEmail');
        $provider = new SecurityUserProvider($users, $this->createStub(MembershipRepository::class));

        $this->expectException(UserNotFoundException::class);
        $provider->loadUserByIdentifier('not-an-email');
    }

    public function testRefreshReloadsASupportedUser(): void
    {
        $userId = Uuid::generate();
        $organizationId = Uuid::generate();
        $provider = $this->provider(
            User::register($userId, new Email('jane@example.com'), 'hashed', new DateTimeImmutable()),
            [Membership::create(Uuid::generate(), $userId, $organizationId, Role::Owner, new DateTimeImmutable())],
        );

        $refreshed = $provider->refreshUser(new SecurityUser('jane@example.com', 'hashed', (string) $organizationId));

        self::assertSame('jane@example.com', $refreshed->getUserIdentifier());
    }

    public function testRefreshRejectsAnUnsupportedUser(): void
    {
        $provider = new SecurityUserProvider(
            $this->createStub(UserRepository::class),
            $this->createStub(MembershipRepository::class),
        );

        $this->expectException(UnsupportedUserException::class);
        $provider->refreshUser($this->createStub(UserInterface::class));
    }

    public function testSupportsOnlyItsOwnUserClass(): void
    {
        $provider = new SecurityUserProvider(
            $this->createStub(UserRepository::class),
            $this->createStub(MembershipRepository::class),
        );

        self::assertTrue($provider->supportsClass(SecurityUser::class));
        self::assertFalse($provider->supportsClass(stdClass::class));
    }

    /**
     * @param list<Membership> $memberships
     */
    private function provider(?User $user, array $memberships): SecurityUserProvider
    {
        $users = $this->createStub(UserRepository::class);
        $users->method('byEmail')->willReturn($user);

        $membershipRepository = $this->createStub(MembershipRepository::class);
        $membershipRepository->method('forUser')->willReturn($memberships);

        return new SecurityUserProvider($users, $membershipRepository);
    }
}
