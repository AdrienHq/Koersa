<?php

declare(strict_types=1);

namespace Koersa\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Koersa\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base class for tests that hit a real database. Boots the kernel and clears
 * the IAM tables before each test so cases stay independent.
 */
abstract class DatabaseTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->executeStatement(
            'TRUNCATE iam_memberships, iam_users, iam_organizations',
        );
    }

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }
}
