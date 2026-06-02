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

// Grants paid-tier access to a user by email. Used for beta testers and
// friends before Stripe is wired; once subscriptions land the Billing
// context will toggle this through a domain event.
#[AsCommand(
    name: 'iam:user:promote-paid',
    description: 'Grant paid-tier access to a user',
)]
final class PromoteUserToPaidCommand extends Command
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

        if ($user->isPaid()) {
            $io->warning(\sprintf('"%s" already has paid access.', $raw));

            return Command::SUCCESS;
        }

        $user->promoteToPaid();
        $this->users->save($user);

        $io->success(\sprintf('"%s" now has paid-tier access.', $raw));

        return Command::SUCCESS;
    }
}
