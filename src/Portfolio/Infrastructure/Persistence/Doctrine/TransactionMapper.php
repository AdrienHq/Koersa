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
            self::normalizeDecimal($entity->quantity),
            self::normalizeDecimal($entity->price),
            self::normalizeDecimal($entity->fee),
            $entity->occurredAt,
            $entity->source,
            $entity->externalId,
            $entity->priceCurrency,
            $entity->feeCurrency,
        );
    }

    // Postgres returns NUMERIC at full scale ("5.000000000000000000"); trim trailing zeros.
    private static function normalizeDecimal(string $value): string
    {
        if (!str_contains($value, '.')) {
            return $value;
        }

        return rtrim(rtrim($value, '0'), '.');
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
        $entity->source = $transaction->source;
        $entity->externalId = $transaction->externalId;
        $entity->priceCurrency = $transaction->priceCurrency;
        $entity->feeCurrency = $transaction->feeCurrency;

        return $entity;
    }
}
