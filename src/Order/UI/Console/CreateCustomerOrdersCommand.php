<?php

namespace App\Order\UI\Console;

use App\Order\Application\Service\DemoOrderCreator;
use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-customer-orders',
    description: 'Create new customer orders',
)]
readonly class CreateCustomerOrdersCommand
{
    public function __construct(
        private DemoOrderCreator $demoOrderCreator,
        private DefaultUserAuthenticator $defaultUserAuthenticator,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Order count')]
        int $orderCount = 0,
        #[Option(description: 'Randomise')]
        bool $random = false,
        #[Option(description: 'Run without persisting changes')]
        bool $dryRun = false,
        #[Option(description: 'Skip timing delays (for testing)')]
        bool $skipTiming = false,
    ): int {
        $io = new SymfonyStyle($input, $output);
        if ($orderCount < 1) {
            $io->error('Order count must be > 0');

            return Command::INVALID;
        }

        if ($random) {
            $orderCount = random_int(0, $orderCount);
            if ($orderCount === 0) {
                $io->note('No orders to create (randomised to 0)');

                return Command::SUCCESS;
            }
        }

        $this->defaultUserAuthenticator->ensureAuthenticated();

        $io->section(sprintf(
            '%sCreating %d new orders',
            $dryRun ? '[DRY RUN] ' : '',
            $orderCount
        ));
        $progress = $io->createProgressBar($orderCount);
        $progress->start();

        $processed = 0;
        $processedIds = [];
        $failed = 0;

        for ($i = 0; $i < $orderCount; ++$i) {
            if (!$skipTiming) {
                sleep(random_int(1, intdiv(300, $orderCount)));
            }

            if ($dryRun) {
                $processedIds[] = 'DRY-RUN-' . ($i + 1);
                ++$processed;
                $progress->advance();
                continue;
            }

            try {
                $result = $this->demoOrderCreator->create('TEST-');

                $processedIds[] = (string) $result->order->getId();
                ++$processed;
            } catch (\Throwable $throwable) {
                ++$failed;
                $this->logger->error('Failed to create customer order (iteration {i})', [
                    'i' => $i + 1,
                    'error' => $throwable->getMessage(),
                ]);
                $this->entityManager->clear();
            }

            $progress->advance();
        }

        $progress->finish();
        $io->newLine(2);

        if ($failed > 0) {
            $io->warning(sprintf('%d order(s) failed — see logs for details.', $failed));
        }

        $io->success(sprintf(
            '%sCreated %d customer orders.',
            $dryRun ? '[DRY RUN] ' : '',
            $processed
        ));

        if ($output->isVerbose()) {
            $io->section('Created Order IDs');
            $io->listing($processedIds);
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
