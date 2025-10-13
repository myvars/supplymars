<?php

namespace App\Purchasing\UI\Console;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Repository\PurchaseOrderRepository;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:accept-purchase-orders',
    description: 'Accept/Reject purchase orders',
)]
readonly class acceptPOsCommand
{
    private const int REJECTION_ODDS = 50; // 1 in 50 gets rejected

    public function __construct(
        private SupplierRepository $suppliers,
        private PurchaseOrderRepository $purchaseOrders,
        private DefaultUserAuthenticator $defaultUserAuthenticator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Number of purchase orders to process')]
        int $poCount = 50,
    ): int {
        $io = new SymfonyStyle($input, $output);

        if ($poCount < 1) {
            $io->error('poCount must be > 0.');

            return Command::INVALID;
        }

        $supplier = $this->suppliers->getRandomSupplier();
        if (!$supplier instanceof Supplier) {
            $io->error('No supplier found');

            return Command::FAILURE;
        }

        $io->section(sprintf('Processing up to %d purchase orders for supplier %s', $poCount, $supplier->getName()));

        $purchaseOrders = $this->getWaitingPurchaseOrders($supplier, $poCount);
        if (!$purchaseOrders) {
            $io->note('No waiting purchase orders.');
            return Command::SUCCESS;
        }

        $this->defaultUserAuthenticator->ensureAuthenticated();

        $progress = $io->createProgressBar(count($purchaseOrders));
        $progress->start();

        $processed = 0;
        $processedIds = [];

        foreach ($purchaseOrders as $purchaseOrder) {
            if (!$purchaseOrder instanceof PurchaseOrder) {
                continue;
            }

            foreach ($purchaseOrder->getPurchaseOrderItems() as $purchaseOrderItem) {
                $purchaseOrderItem->updateItemStatus(newStatus: $this->simulateStatus());
            }

            $processedIds[] = $purchaseOrder->getId().' : '.$purchaseOrder->getStatus()->value;

            ++$processed;
            $progress->advance();
        }

        $this->flusher->flush();

        $progress->finish();
        $io->newLine(2);
        $io->success(sprintf('Processed %d purchase orders.', $processed));

        if ($processed > 0 && $output->isVerbose()) {
            $io->section('Processed PO IDs');
            $io->listing($processedIds);
        }

        return Command::SUCCESS;
    }

    private function simulateStatus(): PurchaseOrderStatus
    {
        return random_int(1, self::REJECTION_ODDS) === 1
            ? PurchaseOrderStatus::REJECTED
            : PurchaseOrderStatus::ACCEPTED;
    }

    private function getWaitingPurchaseOrders(Supplier $supplier, int $poCount): array
    {
        return $this->purchaseOrders->findWaitingPurchaseOrders($supplier, $poCount);
    }
}
