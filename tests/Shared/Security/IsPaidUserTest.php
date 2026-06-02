<?php

declare(strict_types=1);

namespace Koersa\Tests\Shared\Security;

use Koersa\Shared\Security\IsPaidUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

final class IsPaidUserTest extends TestCase
{
    public function testReturnsFalseForEveryoneWhileStripeIsntWiredYet(): void
    {
        $check = new IsPaidUser();
        $user = $this->createStub(UserInterface::class);

        // Until Stripe lands, the paywall always triggers — even for the
        // operator. When this changes, swap the implementation and update
        // this expectation.
        self::assertFalse($check($user));
    }

    public function testReturnsFalseForAnonymous(): void
    {
        self::assertFalse((new IsPaidUser())(null));
    }
}
