<?php

declare(strict_types=1);

namespace Koersa\Reporting\Infrastructure\Console;

use Koersa\IAM\Application\EmailAlreadyInUse;
use Koersa\IAM\Application\RegisterUser;
use Koersa\IAM\Application\RegisterUserHandler;
use Koersa\IAM\Domain\MembershipRepository;
use Koersa\IAM\Domain\UserRepository;
use Koersa\IAM\Domain\ValueObject\Email;
use Koersa\Portfolio\Application\ImportTransactions;
use Koersa\Portfolio\Application\StatementParserRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

// Creates the public demo account (ADR 0011) and replays the Kraken demo CSV
// into it through real ImportTransactions commands. Idempotent: re-running
// skips the user creation if the email already exists, and the aggregate
// dedups imported (source, externalId) so re-imports add no new events.
#[AsCommand(
    name: 'demo:seed',
    description: 'Create or refresh the public demo account with sample trades',
)]
final class SeedDemoUserCommand extends Command
{
    public const string DEMO_EMAIL = 'demo@koersa.local';
    private const string DEMO_PASSWORD = 'demo-locked-account';
    private const string DEMO_ORGANIZATION = 'Demo';
    private const string DEMO_CSV_PATH = __DIR__.'/../../../../tests/Fixtures/Import/kraken_trades_demo.csv';

    public function __construct(
        private readonly RegisterUserHandler $registerUser,
        private readonly UserRepository $users,
        private readonly MembershipRepository $memberships,
        private readonly StatementParserRegistry $parsers,
        private readonly MessageBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = new Email(self::DEMO_EMAIL);
        $created = false;
        try {
            ($this->registerUser)(new RegisterUser(self::DEMO_EMAIL, self::DEMO_PASSWORD, self::DEMO_ORGANIZATION));
            $created = true;
        } catch (EmailAlreadyInUse) {
            // Idempotent: the user already exists from a previous seed run.
        }

        $user = $this->users->byEmail($email);
        if (null === $user) {
            $io->error(\sprintf('Demo user "%s" could not be located after registration.', self::DEMO_EMAIL));

            return Command::FAILURE;
        }

        $memberships = $this->memberships->forUser($user->id());
        if ([] === $memberships) {
            $io->error('Demo user has no membership; expected one created by RegisterUserHandler.');

            return Command::FAILURE;
        }
        $organizationId = $memberships[0]->organizationId();

        $contents = file_get_contents(self::DEMO_CSV_PATH);
        if (false === $contents) {
            $io->error(\sprintf('Demo CSV not readable at %s.', self::DEMO_CSV_PATH));

            return Command::FAILURE;
        }

        $trades = $this->parsers->parserFor('kraken')->parse($contents);
        $this->commandBus->dispatch(new ImportTransactions($organizationId, 'kraken', $trades));

        $io->success(\sprintf(
            '%s demo account "%s" with %d trade(s) from the Kraken demo CSV.',
            $created ? 'Created' : 'Refreshed',
            self::DEMO_EMAIL,
            \count($trades),
        ));

        return Command::SUCCESS;
    }
}
