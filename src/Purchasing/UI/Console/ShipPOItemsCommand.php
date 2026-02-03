<?php

namespace App\Purchasing\UI\Console;

use App\Purchasing\Application\Service\ProcessingSimulator;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\Supplier\SupplierId;
use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
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
    name: 'app:ship-purchase-order-items',
    description: 'Ship PO items',
)]
readonly class ShipPOItemsCommand
{
    public function __construct(
        private SupplierRepository $suppliers,
        private PurchaseOrderItemRepository $purchaseOrderItems,
        private ProcessingSimulator $processingSimulator,
        private DefaultUserAuthenticator $defaultUserAuthenticator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'PO item count to process')]
        int $poItemCount = 50,
        #[Option(description: 'Run without persisting changes')]
        bool $dryRun = false,
        #[Option(description: 'Target a specific supplier by ID')]
        ?int $supplier = null,
        #[Option(description: 'Skip timing/business hours checks')]
        bool $skipTiming = false,
    ): int {
        $io = new SymfonyStyle($input, $output);

        if ($poItemCount < 1) {
            $io->error('poItemCount must be > 0.');

            return Command::INVALID;
        }

        $supplierEntity = $this->resolveSupplier($supplier);
        if (!$supplierEntity instanceof Supplier) {
            $io->error($supplier !== null ? 'Supplier not found: ' . $supplier : 'No supplier found');

            return Command::FAILURE;
        }

        $this->defaultUserAuthenticator->ensureAuthenticated();

        $io->section(sprintf(
            '%sShipping up to %d PO items for supplier %s%s',
            $dryRun ? '[DRY RUN] ' : '',
            $poItemCount,
            $supplierEntity->getName(),
            $skipTiming ? ' (timing checks skipped)' : ''
        ));

        $purchaseOrderItems = $this->getAcceptedPurchaseOrderItems($supplierEntity, $poItemCount);
        if ($purchaseOrderItems === []) {
            $io->note('No accepted PO items to ship.');

            return Command::SUCCESS;
        }

        $progress = $io->createProgressBar(count($purchaseOrderItems));
        $progress->start();

        $shipped = 0;
        $skipped = 0;
        $processedIds = [];

        foreach ($purchaseOrderItems as $purchaseOrderItem) {
            if (!$purchaseOrderItem instanceof PurchaseOrderItem) {
                continue;
            }

            $canShip = $skipTiming || $this->processingSimulator->canShip($purchaseOrderItem);

            if ($canShip) {
                if (!$dryRun) {
                    $purchaseOrderItem->updateItemStatus(newStatus: PurchaseOrderStatus::SHIPPED);
                }

                $processedIds[] = $purchaseOrderItem->getId() . ' : SHIPPED';
                ++$shipped;
            } else {
                ++$skipped;
            }

            $progress->advance();
        }

        if (!$dryRun) {
            $this->flusher->flush();
        }

        $progress->finish();
        $io->newLine(2);

        $io->success(sprintf(
            '%sProcessed %d PO items: %d shipped, %d skipped.',
            $dryRun ? '[DRY RUN] ' : '',
            $shipped + $skipped,
            $shipped,
            $skipped
        ));

        if ($shipped > 0 && $output->isVerbose()) {
            $io->section('Shipped PO Item IDs');
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

    /**
     * @return array<int, PurchaseOrderItem>
     */
    private function getAcceptedPurchaseOrderItems(Supplier $supplier, int $count): array
    {
        return $this->purchaseOrderItems->findPurchaseOrderItemsByStatus(
            $supplier,
            PurchaseOrderStatus::ACCEPTED,
            $count
        );
    }
}
