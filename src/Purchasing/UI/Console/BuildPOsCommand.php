<?php

namespace App\Purchasing\UI\Console;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Repository\OrderRepository;
use App\Purchasing\Application\Service\OrderAllocator;
use App\Shared\Application\FlusherInterface;
use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:build-purchase-orders',
    description: 'Build POs for customer orders',
)]
readonly class BuildPOsCommand
{
    public function __construct(
        private OrderRepository $orders,
        private OrderAllocator $orderAllocator,
        private DefaultUserAuthenticator $defaultUserAuthenticator,
        private FlusherInterface $flusher,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Order count to process')]
        int $orderCount = 50,
        #[Option(description: 'Run without persisting changes')]
        bool $dryRun = false,
    ): int {
        $io = new SymfonyStyle($input, $output);

        if ($orderCount < 1) {
            $io->error('orderCount must be > 0.');

            return Command::INVALID;
        }

        $io->section(sprintf(
            '%sBuilding POs for up to %d customer orders',
            $dryRun ? '[DRY RUN] ' : '',
            $orderCount
        ));

        $customerOrders = $this->getNextCustomerOrders($orderCount);
        if (!$customerOrders) {
            $io->note('No customer orders to process.');

            return Command::SUCCESS;
        }

        $this->defaultUserAuthenticator->ensureAuthenticated();

        $progress = $io->createProgressBar(count($customerOrders));
        $progress->start();

        $processed = 0;
        $posCreated = 0;
        $itemsAllocated = 0;
        $processedIds = [];
        $failed = 0;

        foreach ($customerOrders as $customerOrder) {
            if (!$customerOrder instanceof CustomerOrder) {
                continue;
            }

            try {
                $poCountBefore = $customerOrder->getPurchaseOrders()->count();

                if (!$dryRun) {
                    $this->orderAllocator->process($customerOrder);
                }

                $poCountAfter = $customerOrder->getPurchaseOrders()->count();
                $newPosCount = $poCountAfter - $poCountBefore;
                $posCreated += $newPosCount;

                // Count allocated items across all new POs
                foreach ($customerOrder->getPurchaseOrders() as $po) {
                    $itemsAllocated += $po->getPurchaseOrderItems()->count();
                }

                $processedIds[] = sprintf(
                    'Order #%d: %d PO(s)',
                    $customerOrder->getId(),
                    $newPosCount
                );

                ++$processed;
            } catch (\Throwable $throwable) {
                ++$failed;
                $this->logger->error('Failed to process customer order {id}', [
                    'id' => $customerOrder->getId(),
                    'error' => $throwable->getMessage(),
                ]);
            }

            $progress->advance();
        }

        if (!$dryRun) {
            $this->flusher->flush();
        }

        $progress->finish();
        $io->newLine(2);

        if ($failed > 0) {
            $io->warning(sprintf('%d order(s) failed — see logs for details.', $failed));
        }

        $io->success(sprintf(
            '%sProcessed %d customer orders. Created %d PO(s), allocated %d item(s).',
            $dryRun ? '[DRY RUN] ' : '',
            $processed,
            $posCreated,
            $itemsAllocated
        ));

        if ($output->isVerbose()) {
            $io->section('Processed Order IDs');
            $io->listing($processedIds);
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @return array<int, CustomerOrder>|null
     */
    private function getNextCustomerOrders(int $orderCount): ?array
    {
        return $this->orders->findNextOrdersToBeProcessed($orderCount);
    }
}
