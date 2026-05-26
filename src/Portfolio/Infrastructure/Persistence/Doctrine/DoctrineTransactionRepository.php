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

    public function remove(Uuid $transactionId): void
    {
        $entity = $this->entityManager->find(TransactionEntity::class, (string) $transactionId);

        if (null !== $entity) {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }
    }

    public function find(Uuid $transactionId): ?Transaction
    {
        $entity = $this->entityManager->find(TransactionEntity::class, (string) $transactionId);

        return null === $entity ? null : $this->mapper->toDomain($entity);
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
