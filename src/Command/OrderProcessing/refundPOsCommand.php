<?php

namespace App\Command\OrderProcessing;

use Symfony\Component\Console\Attribute\Argument;
use App\Entity\PurchaseOrder;
use App\Enum\PurchaseOrderStatus;
use App\Service\Order\ProcessOrder;
use App\Service\OrderProcessing\SupplierUtility;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:refund-purchase-orders',
    description: 'Refund/Rebuild purchase orders',
)]
class refundPOsCommand
{
    public function __construct(
        private readonly SupplierUtility $supplierUtility,
        private readonly EntityManagerInterface $entityManager,
        private readonly ProcessOrder $action
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'PO count to process')] string $poCount
    ): int {
        $io = new SymfonyStyle($input, $output);

        $this->supplierUtility->setDefaultUser();

        $purchaseOrders = $this->getRejectedPurchaseOrders($poCount);

        $processedPoCount = 0;
        foreach ($purchaseOrders as $purchaseOrder) {
            $newStatus = PurchaseOrderStatus::REFUNDED;

            foreach ($purchaseOrder->getPurchaseOrderItems() as $purchaseOrderItem) {
                $this->supplierUtility->changePurchaseOrderItemStatus(
                    $purchaseOrderItem,
                    PurchaseOrderStatus::REJECTED,
                    $newStatus
                );
            }

            $this->action->processOrder($purchaseOrder->getCustomerOrder());

            ++$processedPoCount;

            $io->note(sprintf('Purchase order %05d : %s', $purchaseOrder->getId(), $newStatus->value));
        }

        $io->success(sprintf('Processed %d purchase orders', $processedPoCount));

        return Command::SUCCESS;
    }

    private function getRejectedPurchaseOrders(int $poCount): ?array
    {
        return $this->entityManager->getRepository(PurchaseOrder::class)->findBy([
            'status' => PurchaseOrderStatus::REJECTED,
        ], null, $poCount);
    }
}
