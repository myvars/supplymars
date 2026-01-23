<?php

namespace App\Purchasing\UI\Console;

use App\Purchasing\Application\Service\OrderAllocator;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Repository\PurchaseOrderRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:refund-purchase-orders',
    description: 'Refund/Rebuild purchase orders',
)]
readonly class refundPOsCommand
{
    public function __construct(
        private PurchaseOrderRepository $purchaseOrders,
        private OrderAllocator $orderAllocator,
        private DefaultUserAuthenticator $defaultUserAuthenticator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'PO count to process')]
        int $poCount = 50,
    ): int {
        $io = new SymfonyStyle($input, $output);

        if ($poCount < 1) {
            $io->error('poCount must be > 0.');

            return Command::INVALID;
        }

        $purchaseOrders = $this->getRejectedPurchaseOrders($poCount);
        if ($purchaseOrders === []) {
            $io->note('No rejected purchase orders.');

            return Command::SUCCESS;
        }

        $this->defaultUserAuthenticator->ensureAuthenticated();

        $io->section(sprintf('Refunding up to %d rejected purchase orders', $poCount));

        $progress = $io->createProgressBar(count($purchaseOrders));
        $progress->start();

        $processed = 0;
        $processedIds = [];

        foreach ($purchaseOrders as $purchaseOrder) {
            if (!$purchaseOrder instanceof PurchaseOrder) {
                continue;
            }

            foreach ($purchaseOrder->getPurchaseOrderItems() as $purchaseOrderItem) {
                $purchaseOrderItem->updateItemStatus(newStatus: PurchaseOrderStatus::REFUNDED);
            }

            $this->orderAllocator->process($purchaseOrder->getCustomerOrder());

            $processedIds[] = $purchaseOrder->getId() . ' : ' . $purchaseOrder->getStatus()->value;
            ++$processed;
            $progress->advance();
        }

        $this->flusher->flush();

        $progress->finish();
        $io->newLine(2);
        $io->success(sprintf('Processed %d purchase orders.', $processed));

        if ($output->isVerbose()) {
            $io->section('Processed PO IDs');
            $io->listing($processedIds);
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<int, PurchaseOrder>
     */
    private function getRejectedPurchaseOrders(int $poCount): array
    {
        return $this->purchaseOrders->findByStatus(PurchaseOrderStatus::REJECTED, $poCount);
    }
}
