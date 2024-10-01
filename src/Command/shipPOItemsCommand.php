<?php

namespace App\Command;

use App\Entity\PurchaseOrderItem;
use App\Entity\StatusChangeLog;
use App\Entity\Supplier;
use App\Enum\PurchaseOrderStatus;
use App\Service\OrderProcessing\SupplierUtility;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:ship-purchase-order-items',
    description: 'Ship PO items',
)]
class shipPOItemsCommand extends Command
{
    public function __construct(
        private readonly SupplierUtility $supplierUtility,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('poItemCount', InputArgument::REQUIRED, 'PO item count to process');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $poItemCount = $input->getArgument('poItemCount');

        $supplier = $this->supplierUtility->getRandomSupplier();

        if (!$supplier instanceof Supplier) {
            $io->error('No supplier found');

            return Command::FAILURE;
        }

        $this->supplierUtility->setDefaultUser();

        $io->success(sprintf('Shipping PO items for supplier %s', $supplier->getName()));

        $purchaseOrderItems = $this->getPurchaseOrderItems($supplier, $poItemCount);

        $processedPOItemCount = 0;
        foreach ($purchaseOrderItems as $purchaseOrderItem) {
            if (!$this->realWorldPoShippingSimulator($purchaseOrderItem)) {
                continue;
            }

            $this->supplierUtility->changePurchaseOrderItemStatus(
                $purchaseOrderItem,
                PurchaseOrderStatus::ACCEPTED,
                PurchaseOrderStatus::SHIPPED
            );
            //TODO: add tracking number to the PO item
            $processedPOItemCount++;

            $io->note(sprintf('PO Item %05d shipped', $purchaseOrderItem->getId()));
        }

        $io->success(sprintf('Processed %d PO Items', $processedPOItemCount));

        return Command::SUCCESS;
    }

    public function getPurchaseOrderItems(Supplier $supplier, int $count): array {
        return $this->entityManager->getRepository(PurchaseOrderItem::class)
            ->findPurchaseOrderItemsByStatus($supplier, PurchaseOrderStatus::ACCEPTED, $count);
    }

    private function realWorldPoShippingSimulator(PurchaseOrderItem $purchaseOrderItem): bool
    {
        $statusChangeLog = $this->entityManager->getRepository(StatusChangeLog::class)
            ->findPoStatusChangeByStatus($purchaseOrderItem->getId(), PurchaseOrderStatus::ACCEPTED);

        if (!$statusChangeLog) {
            return false;
        }

        // Check it's between 9 AM and 6 PM, and the PO item was accepted more than 2 hours ago
        $now = new \DateTimeImmutable();
        $intervalTime = $now->sub(\DateInterval::createFromDateString('2 hours')); // 2 hours ago
        $startTime = (new \DateTimeImmutable())->setTime(9, 0);  // 9 AM today
        $endTime = (new \DateTimeImmutable())->setTime(18, 0);   // 6 PM today

        if ($now < $startTime || $now > $endTime || $statusChangeLog->getEventTimestamp() > $intervalTime) {
            return false;
        }

        // Simulate real world scenario shipping only some PO items
        return random_int(0, 20) !== 0;
    }
}