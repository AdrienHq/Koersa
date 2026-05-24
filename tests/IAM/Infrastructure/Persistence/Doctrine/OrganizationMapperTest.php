<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Infrastructure\Persistence\Doctrine;

use DateTimeImmutable;
use Koersa\IAM\Domain\Organization;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\OrganizationMapper;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;

final class OrganizationMapperTest extends TestCase
{
    public function testRoundTripPreservesState(): void
    {
        $mapper = new OrganizationMapper();
        $id = Uuid::generate();
        $createdAt = new DateTimeImmutable('2026-05-24 10:00:00');

        $entity = $mapper->toEntity(Organization::create($id, 'Acme Corp', $createdAt));

        self::assertSame((string) $id, $entity->id);
        self::assertSame('Acme Corp', $entity->name);
        self::assertSame('acme-corp', $entity->slug);

        $restored = $mapper->toDomain($entity);

        self::assertTrue($restored->id()->equals($id));
        self::assertSame('Acme Corp', $restored->name());
        self::assertSame('acme-corp', $restored->slug());
        self::assertEquals($createdAt, $restored->createdAt());
    }
}
