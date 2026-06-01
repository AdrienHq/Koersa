<?php

declare(strict_types=1);

namespace Koersa\Tests\IAM\UI;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Koersa\IAM\Domain\Membership;
use Koersa\IAM\Domain\Organization;
use Koersa\IAM\Domain\User;
use Koersa\IAM\Domain\ValueObject\Email;
use Koersa\IAM\Domain\ValueObject\Role;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\DoctrineMembershipRepository;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\DoctrineOrganizationRepository;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\DoctrineUserRepository;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\MembershipMapper;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\OrganizationMapper;
use Koersa\IAM\Infrastructure\Persistence\Doctrine\UserMapper;
use Koersa\IAM\Infrastructure\Security\SecurityUser;
use Koersa\Shared\Domain\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdminControllerTest extends WebTestCase
{
    public function testAdminCanReachTheAdminLanding(): void
    {
        $client = static::createClient();
        $this->seedAdminUser('admin@koersa.local');

        $client->loginUser(new SecurityUser(
            'admin@koersa.local',
            'hash',
            (string) Uuid::generate(),
            isAdmin: true,
            currentRole: Role::Owner,
        ));

        $client->request('GET', '/admin');

        self::assertResponseIsSuccessful();
    }

    public function testNonAdminGetsForbidden(): void
    {
        $client = static::createClient();
        $this->seedRegularUser('regular@example.com');

        $client->loginUser(new SecurityUser(
            'regular@example.com',
            'hash',
            (string) Uuid::generate(),
            isAdmin: false,
            currentRole: Role::Owner,
        ));

        $client->request('GET', '/admin');

        // access_control denies -> 403 (the firewall returns 403, not a
        // redirect to login, because the user IS authenticated, just not
        // authorised).
        self::assertResponseStatusCodeSame(403);
    }

    public function testAnonymousGetsRedirectedToLogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/admin');

        self::assertResponseRedirects('http://localhost/login');
    }

    private function seedAdminUser(string $email): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement(
            'TRUNCATE iam_memberships, iam_users, iam_organizations',
        );

        $userId = Uuid::generate();
        $orgId = Uuid::generate();
        $user = User::register($userId, new Email($email), 'hash', new DateTimeImmutable());
        $user->promoteToAdmin();

        (new DoctrineUserRepository($entityManager, new UserMapper()))->save($user);
        (new DoctrineOrganizationRepository($entityManager, new OrganizationMapper()))
            ->save(Organization::create($orgId, 'Personal', new DateTimeImmutable()));
        (new DoctrineMembershipRepository($entityManager, new MembershipMapper()))
            ->save(Membership::create(Uuid::generate(), $userId, $orgId, Role::Owner, new DateTimeImmutable()));
    }

    private function seedRegularUser(string $email): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement(
            'TRUNCATE iam_memberships, iam_users, iam_organizations',
        );

        $userId = Uuid::generate();
        $orgId = Uuid::generate();
        (new DoctrineUserRepository($entityManager, new UserMapper()))
            ->save(User::register($userId, new Email($email), 'hash', new DateTimeImmutable()));
        (new DoctrineOrganizationRepository($entityManager, new OrganizationMapper()))
            ->save(Organization::create($orgId, 'Personal', new DateTimeImmutable()));
        (new DoctrineMembershipRepository($entityManager, new MembershipMapper()))
            ->save(Membership::create(Uuid::generate(), $userId, $orgId, Role::Owner, new DateTimeImmutable()));
    }
}
