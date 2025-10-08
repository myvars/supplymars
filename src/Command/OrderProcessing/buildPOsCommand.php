<?php

namespace App\Command\OrderProcessing;

use App\Entity\CustomerOrder;
use App\Service\Order\ProcessOrder;
use App\Service\OrderProcessing\SupplierUtility;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:build-purchase-orders',
    description: 'Build POs for customer orders',
)]
class buildPOsCommand
{
    public function __construct(
        private readonly SupplierUtility $supplierUtility,
        private readonly EntityManagerInterface $entityManager,
        private readonly ProcessOrder $orderProcessor,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Order count to process')] string $orderCount,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $customerOrders = $this->getNextCustomerOrders($orderCount);

        if (!$customerOrders) {
            $io->success('No customer orders to process');

            return Command::SUCCESS;
        }

        $this->supplierUtility->setDefaultUser();

        $processedOrders = 0;
        foreach ($customerOrders as $customerOrder) {
            $this->orderProcessor->processOrder($customerOrder);
            ++$processedOrders;

            $io->note(sprintf('Customer order %05d processed', $customerOrder->getId()));
        }

        $io->success(sprintf('%d customer orders processed', $processedOrders));

        return Command::SUCCESS;
    }

    private function getNextCustomerOrders(int $orderCount): ?array
    {
        return $this->entityManager->getRepository(CustomerOrder::class)->findNextOrdersToBeProcessed($orderCount);
    }
}
