<?php

declare(strict_types=1);

namespace Koersa\IAM\Infrastructure\Console;

use InvalidArgumentException;
use Koersa\IAM\Domain\UserRepository;
use Koersa\IAM\Domain\ValueObject\Email;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// Revokes platform admin from a user by email. The inverse of promote-admin;
// same constraint (CLI only).
#[AsCommand(
    name: 'iam:user:demote-admin',
    description: 'Revoke platform admin (ROLE_ADMIN) from a user',
)]
final class DemoteUserFromAdminCommand extends Command
{
    public function __construct(private readonly UserRepository $users)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'The user email to demote');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $raw = $input->getArgument('email');
        \assert(\is_string($raw));

        try {
            $email = new Email($raw);
        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }

        $user = $this->users->byEmail($email);
        if (null === $user) {
            $io->error(\sprintf('No user with email "%s".', $raw));

            return Command::FAILURE;
        }

        if (!$user->isAdmin()) {
            $io->warning(\sprintf('"%s" is not an admin.', $raw));

            return Command::SUCCESS;
        }

        $user->demoteFromAdmin();
        $this->users->save($user);

        $io->success(\sprintf('"%s" is no longer a platform admin.', $raw));

        return Command::SUCCESS;
    }
}
