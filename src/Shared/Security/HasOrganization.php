<?php

declare(strict_types=1);

namespace Koersa\Shared\Security;

use Koersa\Shared\Domain\Uuid;

// Lets a context read the current tenant from the security user without depending on IAM.
interface HasOrganization
{
    public function organizationId(): Uuid;
}
