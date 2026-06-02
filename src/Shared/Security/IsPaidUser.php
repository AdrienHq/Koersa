<?php

declare(strict_types=1);

namespace Koersa\Shared\Security;

use Symfony\Component\Security\Core\User\UserInterface;

// Single source of truth for "does this account have paid-tier access" per
// ADR 0012. Returns false for everyone today — Stripe wiring is the next
// time this comes alive. The paywall UI, the voter and the Tax/Import
// controllers all branch on this single check.
final class IsPaidUser
{
    public function __invoke(?UserInterface $user): bool
    {
        // No subscription model yet. When Stripe lands, this flips into a
        // real check against the Billing context's subscription read model.
        return false;
    }
}
