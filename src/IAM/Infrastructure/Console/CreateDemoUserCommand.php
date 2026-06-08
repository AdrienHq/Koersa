<?php

declare(strict_types=1);

namespace Koersa\IAM\Infrastructure\Console;

use Koersa\IAM\Application\RegisterUser;
use Koersa\IAM\Application\RegisterUserHandler;
use Koersa\IAM\Domain\UserRepository;
use Koersa\IAM\Domain\ValueObject\Email;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// Creates a stable demo account for manual QA. Idempotent: if the user
// already exists, leaves it (and its seeded trades, with their already-
// rebased dates) untouched. That's the "no refresh" guarantee — re-running
// this command never wipes state.
#[AsCommand(
    name: 'iam:user:create-demo',
    description: 'Create a persistent demo account for manual testing (idempotent)',
)]
final class CreateDemoUserCommand extends Command
{
    private const string DEFAULT_EMAIL = 'demo@koersa.local';
    private const string DEFAULT_PASSWORD = 'demo1234';
    private const string DEFAULT_ORG = 'Demo';

    public function __construct(
        private readonly UserRepository $users,
        private readonly RegisterUserHandler $registerUser,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email', self::DEFAULT_EMAIL)
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Plain password', self::DEFAULT_PASSWORD)
            ->addOption('org', null, InputOption::VALUE_REQUIRED, 'Organization name', self::DEFAULT_ORG);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $emailRaw = $input->getOption('email');
        $passwordRaw = $input->getOption('password');
        $orgRaw = $input->getOption('org');
        \assert(\is_string($emailRaw) && \is_string($passwordRaw) && \is_string($orgRaw));

        $email = new Email($emailRaw);

        if (null !== $this->users->byEmail($email)) {
            $io->note(\sprintf('"%s" already exists — leaving it alone.', $emailRaw));

            return Command::SUCCESS;
        }

        // Invoking the handler keeps the demo account identical in shape to
        // a real signup: same org/membership rows, same seeding side effect
        // via DemoTradeSeeder (which rebases dates on first run).
        ($this->registerUser)(new RegisterUser($emailRaw, $passwordRaw, $orgRaw));

        $io->success(\sprintf(
            'Demo account ready. Sign in with %s / %s',
            $emailRaw,
            $passwordRaw,
        ));

        return Command::SUCCESS;
    }
}
