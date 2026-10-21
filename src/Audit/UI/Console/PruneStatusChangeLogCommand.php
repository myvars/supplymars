<?php

declare(strict_types=1);

namespace App\Audit\UI\Console;

use App\Audit\Domain\Repository\StatusChangeLogRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;

#[AsCommand(
    name: 'app:prune-status-change-log',
    description: 'Delete the status history of finished orders and purchase orders older than the retention period',
)]
final class PruneStatusChangeLogCommand
{
    use LockableTrait;

    public function __construct(
        private readonly StatusChangeLogRepository $statusChangeLogs,
        LockFactory $lockFactory,
    ) {
        $this->lockFactory = $lockFactory;
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Months of history to keep')]
        int $months = 12,
        #[Option(description: 'Rows deleted per transaction')]
        int $batchSize = 5000,
    ): int {
        if ($months < 1 || $batchSize < 1) {
            $io->error('months and batch-size must be > 0.');

            return Command::INVALID;
        }

        if (!$this->lock()) {
            $io->note('A prune is already running. This run has been skipped.');

            return Command::SUCCESS;
        }

        try {
            $before = new \DateTimeImmutable(sprintf('-%d months', $months));

            $deleted = $this->statusChangeLogs->deleteFinishedBefore($before, $batchSize);

            $io->success(sprintf('Deleted %d status change log rows from before %s.', $deleted, $before->format('Y-m-d H:i')));

            return Command::SUCCESS;
        } finally {
            $this->release();
        }
    }
}
