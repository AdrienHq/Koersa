<?php

declare(strict_types=1);

namespace Koersa\Shared\Security;

use Symfony\Component\Security\Core\User\UserInterface;

// Single source of truth for "does this account have paid-tier access" per
// ADR 0012. Returns false for everyone today — Stripe wiring is the next
// time this comes alive. The paywall UI and the Tax / PDF / CSV-import
// controllers all branch on this single check.
//
// Intentionally not final: tests swap in a true-returning subclass to
// exercise the paid path end-to-end without having a Billing context yet.
class IsPaidUser
{
    public function __invoke(?UserInterface $user): bool
    {
        // No subscription model yet. When Stripe lands, this flips into a
        // real check against the Billing context's subscription read model.
        return false;
    }
}
