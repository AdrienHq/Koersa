<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Infrastructure\Security;

use DateTimeImmutable;
use Koersa\IAM\Domain\User;
use Koersa\IAM\Domain\UserRepository;
use Koersa\IAM\Domain\ValueObject\Email;
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
    public function testLoadsAUserByEmail(): void
    {
        $provider = new SecurityUserProvider($this->repositoryReturning(
            User::register(Uuid::generate(), new Email('jane@example.com'), 'hashed', new DateTimeImmutable()),
        ));

        $securityUser = $provider->loadUserByIdentifier('jane@example.com');

        self::assertSame('jane@example.com', $securityUser->getUserIdentifier());
        self::assertSame('hashed', $securityUser->getPassword());
    }

    public function testThrowsWhenTheUserIsNotFound(): void
    {
        $provider = new SecurityUserProvider($this->repositoryReturning(null));

        $this->expectException(UserNotFoundException::class);
        $provider->loadUserByIdentifier('ghost@example.com');
    }

    public function testThrowsWhenTheIdentifierIsNotAnEmail(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::never())->method('byEmail');
        $provider = new SecurityUserProvider($repository);

        $this->expectException(UserNotFoundException::class);
        $provider->loadUserByIdentifier('not-an-email');
    }

    public function testRefreshReloadsASupportedUser(): void
    {
        $provider = new SecurityUserProvider($this->repositoryReturning(
            User::register(Uuid::generate(), new Email('jane@example.com'), 'hashed', new DateTimeImmutable()),
        ));

        $refreshed = $provider->refreshUser(new SecurityUser('jane@example.com', 'hashed'));

        self::assertSame('jane@example.com', $refreshed->getUserIdentifier());
    }

    public function testRefreshRejectsAnUnsupportedUser(): void
    {
        $provider = new SecurityUserProvider($this->createStub(UserRepository::class));

        $this->expectException(UnsupportedUserException::class);
        $provider->refreshUser($this->createStub(UserInterface::class));
    }

    public function testSupportsOnlyItsOwnUserClass(): void
    {
        $provider = new SecurityUserProvider($this->createStub(UserRepository::class));

        self::assertTrue($provider->supportsClass(SecurityUser::class));
        self::assertFalse($provider->supportsClass(stdClass::class));
    }

    private function repositoryReturning(?User $user): UserRepository
    {
        $repository = $this->createStub(UserRepository::class);
        $repository->method('byEmail')->willReturn($user);

        return $repository;
    }
}
