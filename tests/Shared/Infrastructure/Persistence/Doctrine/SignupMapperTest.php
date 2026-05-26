<?php

declare(strict_types=1);

namespace Koersa\Tests\Shared\Infrastructure\Persistence\Doctrine;

use DateTimeImmutable;
use Koersa\Shared\Domain\Signup;
use Koersa\Shared\Domain\Uuid;
use Koersa\Shared\Infrastructure\Persistence\Doctrine\SignupMapper;
use PHPUnit\Framework\TestCase;

final class SignupMapperTest extends TestCase
{
    public function testRoundTripPreservesState(): void
    {
        $mapper = new SignupMapper();
        $id = Uuid::generate();
        $signedUpAt = new DateTimeImmutable('2026-05-26 09:00:00');

        $entity = $mapper->toEntity(Signup::reconstitute($id, 'jane@example.be', 'nl', $signedUpAt));

        self::assertSame((string) $id, $entity->id);
        self::assertSame('jane@example.be', $entity->email);
        self::assertSame('nl', $entity->locale);

        $restored = $mapper->toDomain($entity);

        self::assertTrue($restored->id->equals($id));
        self::assertSame('jane@example.be', $restored->email);
        self::assertSame('nl', $restored->locale);
        self::assertEquals($signedUpAt, $restored->signedUpAt);
    }
}
