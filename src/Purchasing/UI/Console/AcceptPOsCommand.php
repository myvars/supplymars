<?php

namespace App\Purchasing\UI\Console;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\Supplier\SupplierId;
use App\Purchasing\Domain\Repository\PurchaseOrderRepository;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:accept-purchase-orders',
    description: 'Accept/Reject purchase orders',
)]
readonly class AcceptPOsCommand
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
        #[Option(description: 'Run without persisting changes')]
        bool $dryRun = false,
        #[Option(description: 'Target a specific supplier by ID')]
        ?int $supplier = null,
    ): int {
        $io = new SymfonyStyle($input, $output);

        if ($poCount < 1) {
            $io->error('poCount must be > 0.');

            return Command::INVALID;
        }

        $supplierEntity = $this->resolveSupplier($supplier);
        if (!$supplierEntity instanceof Supplier) {
            $io->error($supplier !== null ? 'Supplier not found: ' . $supplier : 'No supplier found');

            return Command::FAILURE;
        }

        $io->section(sprintf(
            '%sProcessing up to %d purchase orders for supplier %s',
            $dryRun ? '[DRY RUN] ' : '',
            $poCount,
            $supplierEntity->getName()
        ));

        $purchaseOrders = $this->getWaitingPurchaseOrders($supplierEntity, $poCount);
        if ($purchaseOrders === []) {
            $io->note('No waiting purchase orders.');

            return Command::SUCCESS;
        }

        $this->defaultUserAuthenticator->ensureAuthenticated();

        $progress = $io->createProgressBar(count($purchaseOrders));
        $progress->start();

        $accepted = 0;
        $rejected = 0;
        $processedIds = [];

        foreach ($purchaseOrders as $purchaseOrder) {
            if (!$purchaseOrder instanceof PurchaseOrder) {
                continue;
            }

            $status = $this->simulateStatus();
            if ($status === PurchaseOrderStatus::ACCEPTED) {
                ++$accepted;
            } else {
                ++$rejected;
            }

            if (!$dryRun) {
                foreach ($purchaseOrder->getPurchaseOrderItems() as $purchaseOrderItem) {
                    $purchaseOrderItem->updateItemStatus(newStatus: $status);
                }
            }

            $processedIds[] = $purchaseOrder->getId() . ' : ' . $status->value;
            $progress->advance();
        }

        if (!$dryRun) {
            $this->flusher->flush();
        }

        $progress->finish();
        $io->newLine(2);

        $io->success(sprintf(
            '%sProcessed %d purchase orders: %d accepted, %d rejected.',
            $dryRun ? '[DRY RUN] ' : '',
            $accepted + $rejected,
            $accepted,
            $rejected
        ));

        if ($output->isVerbose()) {
            $io->section('Processed PO IDs');
            $io->listing($processedIds);
        }

        return Command::SUCCESS;
    }

    private function resolveSupplier(?int $supplierId): ?Supplier
    {
        if ($supplierId !== null) {
            return $this->suppliers->get(SupplierId::fromInt($supplierId));
        }

        return $this->suppliers->getRandomSupplier();
    }

    private function simulateStatus(): PurchaseOrderStatus
    {
        return random_int(1, self::REJECTION_ODDS) === 1
            ? PurchaseOrderStatus::REJECTED
            : PurchaseOrderStatus::ACCEPTED;
    }

    /**
     * @return array<int, PurchaseOrder>
     */
    private function getWaitingPurchaseOrders(Supplier $supplier, int $poCount): array
    {
        return $this->purchaseOrders->findWaitingPurchaseOrders($supplier, $poCount);
    }
}
