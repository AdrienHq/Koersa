<?php

declare(strict_types=1);

namespace Koersa\Shared\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Koersa\Shared\Domain\Signup;
use Koersa\Shared\Domain\SignupRepository;
use Koersa\Shared\Infrastructure\Persistence\Doctrine\Entity\SignupEntity;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(SignupRepository::class)]
final class DoctrineSignupRepository implements SignupRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SignupMapper $mapper,
    ) {
    }

    public function save(Signup $signup): void
    {
        $entity = $this->entityManager->find(SignupEntity::class, (string) $signup->id);
        $entity = $this->mapper->toEntity($signup, $entity);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function existsByEmail(string $email): bool
    {
        return null !== $this->entityManager
            ->getRepository(SignupEntity::class)
            ->findOneBy(['email' => strtolower(trim($email))]);
    }
}
