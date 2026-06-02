<?php

declare(strict_types=1);

namespace Koersa\IAM\Infrastructure\Security;

use InvalidArgumentException;
use Koersa\IAM\Domain\MembershipRepository;
use Koersa\IAM\Domain\UserRepository;
use Koersa\IAM\Domain\ValueObject\Email;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<SecurityUser>
 */
final class SecurityUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly MembershipRepository $memberships,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            $email = new Email($identifier);
        } catch (InvalidArgumentException) {
            throw new UserNotFoundException();
        }

        $user = $this->users->byEmail($email);

        if (null === $user) {
            throw new UserNotFoundException();
        }

        $memberships = $this->memberships->forUser($user->id());

        if ([] === $memberships) {
            // A user without an organization cannot act in the app.
            throw new UserNotFoundException();
        }

        return new SecurityUser(
            (string) $user->email(),
            $user->passwordHash(),
            (string) $memberships[0]->organizationId(),
            $user->isAdmin(),
            $memberships[0]->role(),
            $user->isPaid(),
        );
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(\sprintf('Expected "%s", got "%s".', SecurityUser::class, $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class || is_subclass_of($class, SecurityUser::class);
    }
}
