<?php

declare(strict_types=1);

namespace Koersa\Tests\Support;

use Koersa\Shared\Security\IsPaidUser;
use Symfony\Component\Security\Core\User\UserInterface;

// Test-only IsPaidUser replacement, registered in config/services_test.yaml
// to take the place of the real one. Tests mutate $allow on the singleton
// to flip the gate without trying to swap the service in the test container
// (which fails once Twig has resolved PaywallExtension during kernel boot).
//
// Reset to false in PortfolioWebTestCase::setUp so per-test state doesn't
// leak between tests.
final class PaidUserSwitch extends IsPaidUser
{
    public bool $allow = false;

    public function __invoke(?UserInterface $user): bool
    {
        return $this->allow;
    }
}
