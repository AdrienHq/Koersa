<?php

declare(strict_types=1);

namespace Koersa\Tests\Shared\Domain;

use InvalidArgumentException;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;

final class UuidTest extends TestCase
{
    public function testGenerateProducesAValidVersion4Uuid(): void
    {
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            Uuid::generate()->value,
        );
    }

    public function testGenerateProducesDistinctValues(): void
    {
        self::assertNotSame(Uuid::generate()->value, Uuid::generate()->value);
    }

    public function testFromStringNormalizesToLowercase(): void
    {
        self::assertSame(
            'aaaaaaaa-bbbb-4bbb-8bbb-cccccccccccc',
            Uuid::fromString('AAAAAAAA-BBBB-4BBB-8BBB-CCCCCCCCCCCC')->value,
        );
    }

    public function testRejectsAMalformedValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Uuid::fromString('not-a-uuid');
    }

    public function testEquality(): void
    {
        $id = Uuid::fromString('aaaaaaaa-bbbb-4bbb-8bbb-cccccccccccc');

        self::assertTrue($id->equals(Uuid::fromString('aaaaaaaa-bbbb-4bbb-8bbb-cccccccccccc')));
        self::assertFalse($id->equals(Uuid::generate()));
    }
}
