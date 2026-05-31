<?php

declare(strict_types=1);

namespace Koersa\Tests\Shared\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Koersa\Shared\Domain\Signup;
use Koersa\Shared\Domain\Uuid;
use PHPUnit\Framework\TestCase;

final class SignupTest extends TestCase
{
    public function testRegistersAndNormalizesTheEmail(): void
    {
        $signup = Signup::register(Uuid::generate(), '  Jane@Example.BE ', 'fr', new DateTimeImmutable());

        self::assertSame('jane@example.be', $signup->email);
        self::assertSame('fr', $signup->locale);
    }

    public function testRejectsAnInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Signup::register(Uuid::generate(), 'not-an-email', 'fr', new DateTimeImmutable());
    }

    public function testRejectsAnUnsupportedLocale(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Signup::register(Uuid::generate(), 'jane@example.be', 'de', new DateTimeImmutable());
    }
}
