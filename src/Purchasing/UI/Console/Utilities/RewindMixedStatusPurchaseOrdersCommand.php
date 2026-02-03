<?php

namespace App\Purchasing\UI\Console\Utilities;

use App\Purchasing\Application\Service\PurchaseOrderRewindService;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Repository\PurchaseOrderRepository;
use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:rewind-mixed-status-purchase-orders',
    description: 'Rewind purchase orders with mixed item statuses (including rejected items) to pending',
)]
readonly class RewindMixedStatusPurchaseOrdersCommand
{
    public function __construct(
        private DefaultUserAuthenticator $defaultUserAuthenticator,
        private PurchaseOrderRepository $purchaseOrders,
        private PurchaseOrderRewindService $rewindService,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Option(description: 'Maximum number of purchase orders to process')]
        int $limit = 100,
        #[Option(description: 'Number of days back to search')]
        int $daysBack = 30,
        #[Option(description: 'Run without persisting changes')]
        bool $dryRun = false,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $this->defaultUserAuthenticator->ensureAuthenticated();

        $io->section(sprintf(
            '%sFinding purchase orders with mixed item statuses (including rejected) from the last %d days',
            $dryRun ? '[DRY RUN] ' : '',
            $daysBack
        ));

        $purchaseOrders = $this->purchaseOrders->findWithMixedItemStatusesIncludingRejected($daysBack, $limit);

        if ($purchaseOrders === []) {
            $io->success('No purchase orders found with mixed item statuses including rejected.');

            return Command::SUCCESS;
        }

        $io->note(sprintf('Found %d purchase order(s) to process.', count($purchaseOrders)));

        $progress = $io->createProgressBar(count($purchaseOrders));
        $progress->start();

        $processed = 0;
        $processedItems = [];

        foreach ($purchaseOrders as $purchaseOrder) {
            if (!$purchaseOrder instanceof PurchaseOrder) {
                continue;
            }

            $itemStatuses = [];
            foreach ($purchaseOrder->getPurchaseOrderItems() as $item) {
                $itemStatuses[] = $item->getStatus()->value;
            }

            $processedItems[] = sprintf(
                'PO #%06d (ID: %s) - Status: %s - Item statuses: %s',
                $purchaseOrder->getId(),
                $purchaseOrder->getPublicId()->value(),
                $purchaseOrder->getStatus()->value,
                implode(', ', array_unique($itemStatuses))
            );

            if (!$dryRun) {
                $this->rewindService->rewind($purchaseOrder);
            }

            ++$processed;
            $progress->advance();
        }

        $progress->finish();
        $io->newLine(2);

        $io->success(sprintf(
            '%sProcessed %d purchase order(s).',
            $dryRun ? '[DRY RUN] ' : '',
            $processed
        ));

        if ($output->isVerbose() || $dryRun) {
            $io->section($dryRun ? 'Purchase orders that would be rewound:' : 'Rewound purchase orders:');
            $io->listing($processedItems);
        }

        return Command::SUCCESS;
    }
}
