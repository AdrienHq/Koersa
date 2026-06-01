<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Koersa\IAM\Domain\Organization;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;

final class OrganizationTest extends TestCase
{
    public function testCreateDerivesASlugFromTheNameWithUuidSuffix(): void
    {
        $id = Uuid::generate();
        $organization = Organization::create($id, 'Acme Corp', new DateTimeImmutable());

        self::assertSame('Acme Corp', $organization->name());
        // The slug prefix is human-readable, the suffix disambiguates so two
        // orgs that share a name (e.g. both default 'Personal') don't collide.
        self::assertSame('acme-corp-'.substr($id->value, 0, 8), $organization->slug());
    }

    public function testRenameUpdatesNameAndKeepsTheSameUuidSuffix(): void
    {
        $id = Uuid::generate();
        $organization = Organization::create($id, 'Acme Corp', new DateTimeImmutable());

        $organization->rename('Globex Belgium');

        self::assertSame('Globex Belgium', $organization->name());
        self::assertSame('globex-belgium-'.substr($id->value, 0, 8), $organization->slug());
    }

    public function testRejectsANameThatProducesAnEmptyPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Organization::create(Uuid::generate(), '   ###   ', new DateTimeImmutable());
    }
}
