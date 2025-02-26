<?php

namespace App\Command\OrderProcessing;

use App\Entity\PurchaseOrder;
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
    name: 'app:accept-purchase-orders',
    description: 'Accept/Reject purchase orders',
)]
class acceptPOsCommand extends Command
{
    public function __construct(
        private readonly SupplierUtility $supplierUtility,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('poCount', InputArgument::REQUIRED, 'PO count to process');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $poCount = $input->getArgument('poCount');

        $supplier = $this->supplierUtility->getRandomSupplier();

        if (!$supplier instanceof Supplier) {
            $io->error('No supplier found');

            return Command::FAILURE;
        }

        $this->supplierUtility->setDefaultUser();

        $io->success(sprintf('Processing purchase orders for supplier %s', $supplier->getName()));

        $purchaseOrders = $this->getWaitingPurchaseOrders($supplier, $poCount);

        $processedPoCount = 0;
        foreach ($purchaseOrders as $purchaseOrder) {
            $newStatus = $this->realWorldPoStatusSimulator();

            foreach ($purchaseOrder->getPurchaseOrderItems() as $purchaseOrderItem) {
                $this->supplierUtility->changePurchaseOrderItemStatus(
                    $purchaseOrderItem,
                    PurchaseOrderStatus::PROCESSING,
                    $newStatus
                );
            }

            ++$processedPoCount;

            $io->note(sprintf('Purchase order %05d : %s', $purchaseOrder->getId(), $newStatus->value));
        }

        $io->success(sprintf('Processed %d purchase orders', $processedPoCount));

        return Command::SUCCESS;
    }

    private function getWaitingPurchaseOrders(Supplier $supplier, int $poCount): ?array
    {
        return $this->entityManager->getRepository(PurchaseOrder::class)
            ->findWaitingPurchaseOrders($supplier, $poCount);
    }

    private function realWorldPoStatusSimulator(): PurchaseOrderStatus
    {
        // Simulate real world scenario by rejecting some POs
        return 1 === random_int(1, 50) ? PurchaseOrderStatus::REJECTED : PurchaseOrderStatus::ACCEPTED;
    }
}
