<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Infrastructure\Console;

use Doctrine\DBAL\Connection;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\OffsetCursor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'portfolio:projections:rebuild',
    description: 'Rebuild the Portfolio read models from the event store',
)]
final class RebuildProjectionsCommand extends Command
{
    public function __construct(
        private readonly MessageRepository $messages,
        private readonly MessageDispatcher $dispatcher,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->connection->executeStatement('TRUNCATE TABLE portfolio_transactions');

        $cursor = OffsetCursor::fromStart();
        $total = 0;

        do {
            $page = $this->messages->paginate($cursor);
            $count = 0;
            foreach ($page as $message) {
                $this->dispatcher->dispatch($message);
                ++$count;
            }
            $cursor = $page->getReturn();
            $total += $count;
        } while ($count > 0);

        $io->success(\sprintf('Replayed %d event(s) into the Portfolio read models.', $total));

        return Command::SUCCESS;
    }
}
