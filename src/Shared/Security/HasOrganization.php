<?php

declare(strict_types=1);

namespace Koersa\Shared\Security;

use Koersa\Shared\Domain\Uuid;

/**
 * Contract for an authenticated user that acts within an organization. Lives in
 * Shared so other contexts can read the current tenant from the security user
 * without depending on IAM. IAM's SecurityUser implements it.
 */
interface HasOrganization
{
    public function organizationId(): Uuid;
}
