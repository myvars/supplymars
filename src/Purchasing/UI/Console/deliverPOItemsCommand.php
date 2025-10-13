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
    name: 'app:deliver-purchase-order-items',
    description: 'Deliver PO items',
)]
readonly class deliverPOItemsCommand
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

        $io->section(sprintf('Delivering up to %d PO items for supplier %s',
            $poItemCount,
            $supplier->getName()
        ));

        $purchaseOrderItems = $this->getShippedPurchaseOrderItems($supplier, $poItemCount);
        if (!$purchaseOrderItems) {
            $io->note('No shipped PO items to deliver.');
            return Command::SUCCESS;
        }

        $this->defaultUserAuthenticator->ensureAuthenticated();

        $progress = $io->createProgressBar(count($purchaseOrderItems));
        $progress->start();

        $processed = 0;
        $processedIds = [];

        foreach ($purchaseOrderItems as $purchaseOrderItem) {
            if (!$purchaseOrderItem instanceof PurchaseOrderItem) {
                continue;
            }

            if ($this->realWorldPoDeliverySimulator($purchaseOrderItem)) {
                $purchaseOrderItem->updateItemStatus(newStatus: PurchaseOrderStatus::DELIVERED);

                $processedIds[] = $purchaseOrderItem->getId() . ' : ' . $purchaseOrderItem->getStatus()->value;
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

    private function getShippedPurchaseOrderItems(Supplier $supplier, int $count): array
    {
        return $this->purchaseOrderItems->findPurchaseOrderItemsByStatus(
            $supplier,
            PurchaseOrderStatus::SHIPPED,
            $count
        );
    }

    public function getShippedPoStatusChange(PurchaseOrderItem $purchaseOrderItem): ?StatusChangeLog
    {
        return $this->statusChangeLogs->findPoStatusChangeByStatus(
            $purchaseOrderItem->getId(),
            PurchaseOrderStatus::SHIPPED
        );
    }

    private function realWorldPoDeliverySimulator(PurchaseOrderItem $purchaseOrderItem): bool
    {
        $statusChangeLog = $this->getShippedPoStatusChange($purchaseOrderItem);
        if (!$statusChangeLog instanceof StatusChangeLog) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $intervalTime = $now->sub(\DateInterval::createFromDateString('12 hours'));
        $startTime = (new \DateTimeImmutable())->setTime(7, 0);
        $endTime = (new \DateTimeImmutable())->setTime(21, 0);

        if ($now < $startTime || $now > $endTime || $statusChangeLog->getEventTimestamp() > $intervalTime) {
            return false;
        }

        return 0 !== random_int(0, 20);
    }
}
