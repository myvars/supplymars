<?php

namespace App\Command\OrderProcessing;

use App\Entity\PurchaseOrderItem;
use App\Entity\StatusChangeLog;
use App\Entity\Supplier;
use App\Enum\PurchaseOrderStatus;
use App\Service\OrderProcessing\SupplierUtility;
use Doctrine\ORM\EntityManagerInterface;
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
class deliverPOItemsCommand
{
    public function __construct(private readonly SupplierUtility $supplierUtility, private readonly EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'PO item count to process')] string $poItemCount,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $supplier = $this->supplierUtility->getRandomSupplier();

        if (!$supplier instanceof Supplier) {
            $io->error('No supplier found');

            return Command::FAILURE;
        }

        $this->supplierUtility->setDefaultUser();

        $io->success(sprintf('Delivering PO items for supplier %s', $supplier->getName()));

        $purchaseOrderItems = $this->getPurchaseOrderItems($supplier, $poItemCount);

        $processedPOItemCount = 0;
        foreach ($purchaseOrderItems as $purchaseOrderItem) {
            if (!$this->realWorldPoShippingSimulator($purchaseOrderItem)) {
                continue;
            }

            $this->supplierUtility->changePurchaseOrderItemStatus(
                $purchaseOrderItem,
                PurchaseOrderStatus::SHIPPED,
                PurchaseOrderStatus::DELIVERED
            );
            ++$processedPOItemCount;

            $io->note(sprintf('PO Item %05d delivered', $purchaseOrderItem->getId()));
        }

        $io->success(sprintf('Processed %d PO Items', $processedPOItemCount));

        return Command::SUCCESS;
    }

    public function getPurchaseOrderItems(Supplier $supplier, int $count): array
    {
        return $this->entityManager->getRepository(PurchaseOrderItem::class)
            ->findPurchaseOrderItemsByStatus($supplier, PurchaseOrderStatus::SHIPPED, $count);
    }

    private function realWorldPoShippingSimulator(PurchaseOrderItem $purchaseOrderItem): bool
    {
        $statusChangeLog = $this->entityManager->getRepository(StatusChangeLog::class)
            ->findPoStatusChangeByStatus($purchaseOrderItem->getId(), PurchaseOrderStatus::SHIPPED);

        if (!$statusChangeLog) {
            return false;
        }

        // Check it's between 7 AM and 10 PM, and the PO item was accepted more than 2 hours ago
        $now = new \DateTimeImmutable();
        $intervalTime = $now->sub(\DateInterval::createFromDateString('12 hours')); // 12 hours ago
        $startTime = new \DateTimeImmutable()->setTime(7, 0);  // 7 AM today
        $endTime = new \DateTimeImmutable()->setTime(21, 0);   // 9 PM today

        if ($now < $startTime || $now > $endTime || $statusChangeLog->getEventTimestamp() > $intervalTime) {
            return false;
        }

        // Simulate real world scenario delivering only some PO items
        return 0 !== random_int(0, 20);
    }
}
