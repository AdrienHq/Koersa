<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Infrastructure\Persistence\Doctrine;

use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Infrastructure\Persistence\Doctrine\Entity\TransactionEntity;
use Koersa\Shared\Domain\Uuid;

final class TransactionMapper
{
    public function toDomain(TransactionEntity $entity): Transaction
    {
        return Transaction::reconstitute(
            Uuid::fromString($entity->id),
            Uuid::fromString($entity->organizationId),
            $entity->asset,
            $entity->side,
            $entity->quantity,
            $entity->price,
            $entity->fee,
            $entity->occurredAt,
        );
    }

    public function toEntity(Transaction $transaction, ?TransactionEntity $entity = null): TransactionEntity
    {
        $entity ??= new TransactionEntity();
        $entity->id = (string) $transaction->id;
        $entity->organizationId = (string) $transaction->organizationId;
        $entity->asset = $transaction->asset;
        $entity->side = $transaction->side;
        $entity->quantity = $transaction->quantity;
        $entity->price = $transaction->price;
        $entity->fee = $transaction->fee;
        $entity->occurredAt = $transaction->occurredAt;

        return $entity;
    }
}
