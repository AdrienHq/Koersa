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
    public function testCreateDerivesASlugFromTheName(): void
    {
        $organization = Organization::create(Uuid::generate(), 'Acme Corp', new DateTimeImmutable());

        self::assertSame('Acme Corp', $organization->name());
        self::assertSame('acme-corp', $organization->slug());
    }

    public function testRenameUpdatesNameAndSlug(): void
    {
        $organization = Organization::create(Uuid::generate(), 'Acme Corp', new DateTimeImmutable());

        $organization->rename('Globex Belgium');

        self::assertSame('Globex Belgium', $organization->name());
        self::assertSame('globex-belgium', $organization->slug());
    }

    public function testRejectsANameThatProducesAnEmptySlug(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Organization::create(Uuid::generate(), '   ###   ', new DateTimeImmutable());
    }
}
