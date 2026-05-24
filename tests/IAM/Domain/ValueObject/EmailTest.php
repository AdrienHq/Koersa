<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\Domain\ValueObject;

use InvalidArgumentException;
use Koersa\IAM\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testNormalizesCaseAndSurroundingWhitespace(): void
    {
        self::assertSame('john.doe@example.com', (new Email('  John.Doe@Example.COM '))->value);
    }

    public function testRejectsAnInvalidAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('not-an-email');
    }

    public function testEquality(): void
    {
        self::assertTrue((new Email('a@b.com'))->equals(new Email('A@B.com')));
        self::assertFalse((new Email('a@b.com'))->equals(new Email('c@d.com')));
    }
}
