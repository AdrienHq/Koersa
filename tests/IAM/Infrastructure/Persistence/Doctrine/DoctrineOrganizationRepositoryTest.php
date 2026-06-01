<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Infrastructure\Persistence\Doctrine;

use DateTimeImmutable;
use Koersa\IAM\Domain\Organization;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\DoctrineOrganizationRepository;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\OrganizationMapper;
use Koersa\Shared\Domain\Uuid;
use Koersa\Tests\Support\DatabaseTestCase;

final class DoctrineOrganizationRepositoryTest extends DatabaseTestCase
{
    private DoctrineOrganizationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DoctrineOrganizationRepository($this->entityManager, new OrganizationMapper());
    }

    public function testSavesAndLoadsByIdAndSlug(): void
    {
        $id = Uuid::generate();
        $this->repository->save(Organization::create($id, 'Acme Corp', new DateTimeImmutable('2026-05-25 09:00:00')));
        $this->entityManager->clear();

        $expectedSlug = 'acme-corp-'.substr($id->value, 0, 8);

        $byId = $this->repository->byId($id);
        self::assertNotNull($byId);
        self::assertSame('Acme Corp', $byId->name());
        self::assertSame($expectedSlug, $byId->slug());

        self::assertNotNull($this->repository->bySlug($expectedSlug));
    }

    public function testReturnsNullWhenMissing(): void
    {
        self::assertNull($this->repository->byId(Uuid::generate()));
        self::assertNull($this->repository->bySlug('does-not-exist'));
    }
}
