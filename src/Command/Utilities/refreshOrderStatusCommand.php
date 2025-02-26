<?php

namespace App\Command\Utilities;

use App\Entity\CustomerOrder;
use App\Service\OrderProcessing\RefreshOrderStatus;
use App\Service\OrderProcessing\SupplierUtility;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:refresh-order-status',
    description: 'Refresh order, item, PO status',
)]
class refreshOrderStatusCommand extends Command
{
    public function __construct(
        private readonly SupplierUtility $supplierUtility,
        private readonly EntityManagerInterface $entityManager,
        private readonly RefreshOrderStatus $refreshOrderStatus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('orderCount', InputArgument::REQUIRED, 'Number of orders to process');
        $this->addArgument('offset', InputArgument::OPTIONAL, 'Offset to start from', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $orderCount = $input->getArgument('orderCount');
        $offset = $input->getArgument('offset');

        $this->supplierUtility->setDefaultUser();

        $io->success('Refreshing Orders');

        $customerOrders = $this->getCustomerOrders($orderCount, $offset);

        $processedCount = 0;
        $refreshedCount = 0;
        foreach ($customerOrders as $customerOrder) {
            $refreshLog = $this->refreshOrderStatus->refresh($customerOrder);
            if ([] !== $refreshLog) {
                foreach ($refreshLog as $log) {
                    $io->note($log);
                }

                ++$refreshedCount;
            }

            ++$processedCount;
        }

        $io->success(sprintf('Refreshed %d of %d orders', $refreshedCount, $processedCount));

        return Command::SUCCESS;
    }

    public function getCustomerOrders(int $count, int $offset): array
    {
        return $this->entityManager->getRepository(CustomerOrder::class)
            ->findBy([], ['createdAt' => 'ASC'], $count, $offset);
    }
}
