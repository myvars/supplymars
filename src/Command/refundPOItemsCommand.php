<?php

namespace App\Command;

use App\Entity\PurchaseOrderItem;
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
    name: 'app:refund-purchase-order-items',
    description: 'Refund PO items',
)]
class refundPOItemsCommand extends Command
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

        $io->success(sprintf('Refunding PO items for supplier %s', $supplier->getName()));

        $purchaseOrderItems = $this->getPurchaseOrderItems($supplier, $poItemCount);

        $processedPOItemCount = 0;
        foreach ($purchaseOrderItems as $purchaseOrderItem) {
            if (!$this->realWorldPoShippingSimulator($purchaseOrderItem)) {
                continue;
            }

            $this->supplierUtility->changePurchaseOrderItemStatus(
                $purchaseOrderItem,
                PurchaseOrderStatus::REJECTED,
                PurchaseOrderStatus::REFUNDED
            );
            //TODO: add tracking number to the PO item
            $processedPOItemCount++;

            $io->note(sprintf('PO Item %05d refunded', $purchaseOrderItem->getId()));
        }

        $io->success(sprintf('Processed %d PO Items', $processedPOItemCount));

        return Command::SUCCESS;
    }

    public function getPurchaseOrderItems(Supplier $supplier, int $count): array {
        return $this->entityManager->getRepository(PurchaseOrderItem::class)
            ->findPurchaseOrderItemsByStatus($supplier, PurchaseOrderStatus::REJECTED, $count);
    }

    private function realWorldPoShippingSimulator(PurchaseOrderItem $purchaseOrderItem): bool
    {
        $customerOrder = $purchaseOrderItem->getCustomerOrderItem()->getCustomerOrder();

        foreach ($customerOrder->getCustomerOrderItems() as $customerOrderItem) {
            if ($customerOrderItem->getOutstandingQty() > 0) {
                return false;
            }
        }

        return true;
    }
}