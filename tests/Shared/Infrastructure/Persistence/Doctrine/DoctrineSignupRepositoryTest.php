<?php

declare(strict_types=1);

namespace Koersa\Tests\Shared\Infrastructure\Persistence\Doctrine;

use DateTimeImmutable;
use Koersa\Shared\Domain\Signup;
use Koersa\Shared\Domain\Uuid;
use Koersa\Shared\Infrastructure\Persistence\Doctrine\DoctrineSignupRepository;
use Koersa\Shared\Infrastructure\Persistence\Doctrine\SignupMapper;
use Koersa\Tests\Support\DatabaseTestCase;

final class DoctrineSignupRepositoryTest extends DatabaseTestCase
{
    private DoctrineSignupRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DoctrineSignupRepository($this->entityManager, new SignupMapper());
    }

    public function testSavesAndDetectsAnEmailCaseInsensitively(): void
    {
        $this->repository->save(Signup::register(Uuid::generate(), 'jane@example.be', 'fr', new DateTimeImmutable()));
        $this->entityManager->clear();

        self::assertTrue($this->repository->existsByEmail('jane@example.be'));
        self::assertTrue($this->repository->existsByEmail('  JANE@EXAMPLE.BE '));
        self::assertFalse($this->repository->existsByEmail('someone-else@example.be'));
    }
}
