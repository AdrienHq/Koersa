<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Infrastructure\Persistence\Doctrine;

use DateTimeImmutable;
use Koersa\Portfolio\Domain\Transaction;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Portfolio\Infrastructure\Persistence\Doctrine\TransactionMapper;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;

final class TransactionMapperTest extends TestCase
{
    public function testRoundTripPreservesState(): void
    {
        $mapper = new TransactionMapper();
        $id = Uuid::generate();
        $organizationId = Uuid::generate();
        $occurredAt = new DateTimeImmutable('2026-05-25 10:00:00');

        $entity = $mapper->toEntity(
            Transaction::reconstitute($id, $organizationId, 'BTC', Side::Buy, '0.5', '40000', '10', $occurredAt),
        );

        self::assertSame((string) $id, $entity->id);
        self::assertSame('BTC', $entity->asset);
        self::assertSame(Side::Buy, $entity->side);
        self::assertSame('0.5', $entity->quantity);

        $restored = $mapper->toDomain($entity);

        self::assertTrue($restored->id->equals($id));
        self::assertTrue($restored->organizationId->equals($organizationId));
        self::assertSame(Side::Buy, $restored->side);
        self::assertSame('0.5', $restored->quantity);
        self::assertEquals($occurredAt, $restored->occurredAt);
    }
}
