<?php

declare(strict_types=1);

namespace Koersa\Tests\Smoke;

use Koersa\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class KernelBootTest extends KernelTestCase
{
    public function testKernelBootsAndCompilesTheContainer(): void
    {
        self::bootKernel();

        self::assertSame('test', self::$kernel->getEnvironment());
        self::assertTrue(self::getContainer()->has('kernel'));
    }

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }
}
