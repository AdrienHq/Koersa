<?php

declare(strict_types=1);

namespace Koersa\IAM\Application;

use Koersa\IAM\Domain\Membership;
use Koersa\IAM\Domain\MembershipRepository;
use Koersa\IAM\Domain\Organization;
use Koersa\IAM\Domain\OrganizationRepository;
use Koersa\IAM\Domain\User;
use Koersa\IAM\Domain\UserRepository;
use Koersa\IAM\Domain\ValueObject\Email;
use Koersa\IAM\Domain\ValueObject\Role;
use Koersa\Shared\Domain\Uuid;
use Psr\Clock\ClockInterface;

final class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly OrganizationRepository $organizations,
        private readonly MembershipRepository $memberships,
        private readonly PasswordHasher $passwordHasher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(RegisterUser $command): void
    {
        $email = new Email($command->email);

        if (null !== $this->users->byEmail($email)) {
            throw EmailAlreadyInUse::withEmail($email);
        }

        $now = $this->clock->now();

        $user = User::register(Uuid::generate(), $email, $this->passwordHasher->hash($command->plainPassword), $now);
        $organization = Organization::create(Uuid::generate(), $command->organizationName, $now);
        $membership = Membership::create(Uuid::generate(), $user->id(), $organization->id(), Role::Owner, $now);

        // The user is saved first so a duplicate-email race fails before any
        // organization or membership row is written. A single transaction
        // around all three arrives with the command bus in iteration 2.
        $this->users->save($user);
        $this->organizations->save($organization);
        $this->memberships->save($membership);
    }
}
