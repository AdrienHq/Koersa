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

// Grants platform admin to a user by email. Per ADR 0010 this is the only
// way to mint a ROLE_ADMIN — no UI on purpose.
#[AsCommand(
    name: 'iam:user:promote-admin',
    description: 'Grant platform admin (ROLE_ADMIN) to a user',
)]
final class PromoteUserToAdminCommand extends Command
{
    public function __construct(private readonly UserRepository $users)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'The user email to promote');
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

        if ($user->isAdmin()) {
            $io->warning(\sprintf('"%s" is already an admin.', $raw));

            return Command::SUCCESS;
        }

        $user->promoteToAdmin();
        $this->users->save($user);

        $io->success(\sprintf('"%s" is now a platform admin.', $raw));

        return Command::SUCCESS;
    }
}
