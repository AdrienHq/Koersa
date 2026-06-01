<?php

declare(strict_types=1);

namespace Koersa\IAM\Domain;

use Koersa\Shared\Domain\Uuid;

interface OrganizationRepository
{
    public function save(Organization $organization): void;

    public function byId(Uuid $id): ?Organization;

    public function bySlug(string $slug): ?Organization;

    public function count(): int;

    /** @return list<Organization> most recent first */
    public function recent(int $limit = 50): array;
}
