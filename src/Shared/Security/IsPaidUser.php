<?php

declare(strict_types=1);

namespace Koersa\Shared\Security;

use Symfony\Component\Security\Core\User\UserInterface;

// Single source of truth for "does this account have paid-tier access".
// Composed of (in order):
//   1. ROLE_ADMIN — the operator always sees the full app, otherwise the
//      paywall locks them out of their own product.
//   2. (future) per-user is_paid flag granted via the CLI for beta testers
//      and friends, before Stripe is wired.
//   3. (future) active subscription row from the Billing context, when
//      Stripe lands.
//
// Intentionally not final: tests swap in a true-returning subclass to
// exercise the paid path end-to-end without going through the role layer.
class IsPaidUser
{
    public function __invoke(?UserInterface $user): bool
    {
        if (null === $user) {
            return false;
        }

        if (\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        return false;
    }
}
