<?php

declare(strict_types=1);

namespace Koersa\Shared\Security;

// Lets IsPaidUser check whether the authenticated user holds paid-tier
// access without depending on the IAM context. SecurityUser implements
// this; the flag is sourced from User::isPaid() in the IAM domain.
interface HasPaidAccess
{
    public function isPaid(): bool;
}
