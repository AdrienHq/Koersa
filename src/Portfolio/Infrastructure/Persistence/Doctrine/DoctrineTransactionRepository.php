<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\TransactionRepository;
use Koersa\Portfolio\Infrastructure\Persistence\Doctrine\Entity\TransactionEntity;
use Koersa\Shared\Domain\Uuid;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(TransactionRepository::class)]
final class DoctrineTransactionRepository implements TransactionRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TransactionMapper $mapper,
    ) {
    }

    public function save(Transaction $transaction): void
    {
        $entity = $this->entityManager->find(TransactionEntity::class, (string) $transaction->id);
        $entity = $this->mapper->toEntity($transaction, $entity);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function forOrganization(Uuid $organizationId): array
    {
        $entities = $this->entityManager
            ->getRepository(TransactionEntity::class)
            ->findBy(['organizationId' => (string) $organizationId], ['occurredAt' => 'DESC']);

        return array_map(
            fn (TransactionEntity $entity): Transaction => $this->mapper->toDomain($entity),
            $entities,
        );
    }
}
