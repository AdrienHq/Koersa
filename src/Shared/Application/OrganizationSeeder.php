<?php

declare(strict_types=1);

namespace Koersa\Shared\Application;

use Koersa\Shared\Domain\Uuid;

// IAM raises a registration; Reporting picks it up by implementing this and
// fills the new org with the demo trades. The Shared seam lets IAM stay free
// of Portfolio/Reporting imports while still wiring the side effect at
// registration time. Per ADR 0012.
//
// Implementations are best-effort: a seeding failure must not abort the
// surrounding registration. Log internally and return.
interface OrganizationSeeder
{
    public function seed(Uuid $organizationId): void;
}
