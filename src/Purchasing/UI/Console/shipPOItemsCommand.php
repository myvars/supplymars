<?php

namespace App\Purchasing\UI\Console;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Audit\Domain\Repository\StatusChangeLogRepository;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
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
    name: 'app:ship-purchase-order-items',
    description: 'Ship PO items',
)]
readonly class shipPOItemsCommand
{
    public function __construct(
        private SupplierRepository $suppliers,
        private PurchaseOrderItemRepository $purchaseOrderItems,
        private StatusChangeLogRepository $statusChangeLogs,
        private DefaultUserAuthenticator $defaultUserAuthenticator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'PO item count to process')]
        int $poItemCount = 50,
    ): int {
        $io = new SymfonyStyle($input, $output);

        if ($poItemCount < 1) {
            $io->error('poItemCount must be > 0.');

            return Command::INVALID;
        }

        $supplier = $this->suppliers->getRandomSupplier();
        if (!$supplier instanceof Supplier) {
            $io->error('No supplier found');

            return Command::FAILURE;
        }

        $this->defaultUserAuthenticator->ensureAuthenticated();

        $io->section(sprintf('Shipping up to %d PO items for supplier %s',
            $poItemCount,
            $supplier->getName(),
        ));

        $purchaseOrderItems = $this->getAcceptedPurchaseOrderItems($supplier, $poItemCount);
        if (!$purchaseOrderItems) {
            $io->note('No accepted PO items to ship.');

            return Command::SUCCESS;
        }

        $progress = $io->createProgressBar(count($purchaseOrderItems));
        $progress->start();

        $processed = 0;
        $processedIds = [];

        foreach ($purchaseOrderItems as $purchaseOrderItem) {
            if (!$purchaseOrderItem instanceof PurchaseOrderItem) {
                continue;
            }

            if ($this->realWorldPoShippingSimulator($purchaseOrderItem)) {
                $purchaseOrderItem->updateItemStatus(newStatus: PurchaseOrderStatus::SHIPPED);

                $processedIds[] = $purchaseOrderItem->getId().' : '.$purchaseOrderItem->getStatus()->value;
                ++$processed;
            }

            $progress->advance();
        }

        $this->flusher->flush();

        $progress->finish();
        $io->newLine(2);
        $io->success(sprintf('Processed %d PO Items.', $processed));

        if ($processed > 0 && $output->isVerbose()) {
            $io->section('Processed PO Item IDs');
            $io->listing($processedIds);
        }

        return Command::SUCCESS;
    }

    private function getAcceptedPurchaseOrderItems(Supplier $supplier, int $count): array
    {
        return $this->purchaseOrderItems->findPurchaseOrderItemsByStatus(
            $supplier,
            PurchaseOrderStatus::ACCEPTED,
            $count
        );
    }

    public function getAcceptedPoStatusChange(PurchaseOrderItem $purchaseOrderItem): ?StatusChangeLog
    {
        return $this->statusChangeLogs->findPoStatusChangeByStatus(
            $purchaseOrderItem->getId(),
            PurchaseOrderStatus::ACCEPTED
        );
    }

    private function realWorldPoShippingSimulator(PurchaseOrderItem $purchaseOrderItem): bool
    {
        $statusChangeLog = $this->getAcceptedPoStatusChange($purchaseOrderItem);
        if (!$statusChangeLog) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $intervalTime = $now->sub(\DateInterval::createFromDateString('2 hours'));
        $startTime = new \DateTimeImmutable()->setTime(9, 0);
        $endTime = new \DateTimeImmutable()->setTime(18, 0);

        if ($now < $startTime || $now > $endTime || $statusChangeLog->getEventTimestamp() > $intervalTime) {
            return false;
        }

        return 0 !== random_int(0, 20);
    }
}
