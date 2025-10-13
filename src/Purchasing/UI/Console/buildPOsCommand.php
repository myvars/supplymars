<?php

namespace App\Purchasing\UI\Console;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Repository\OrderRepository;
use App\Purchasing\Application\Service\OrderAllocator;
use App\Shared\Application\FlusherInterface;
use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
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
readonly class buildPOsCommand
{
    public function __construct(
        private OrderRepository $orders,
        private OrderAllocator $orderAllocator,
        private DefaultUserAuthenticator $defaultUserAuthenticator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Order count to process')]
        int $orderCount = 50,
    ): int {
        $io = new SymfonyStyle($input, $output);

        if ($orderCount < 1) {
            $io->error('orderCount must be > 0.');
            return Command::INVALID;
        }

        $io->section(sprintf('Building POs for up to %d customer orders', $orderCount));

        $customerOrders = $this->getNextCustomerOrders($orderCount);
        if (!$customerOrders) {
            $io->note('No customer orders to process.');
            return Command::SUCCESS;
        }

        $this->defaultUserAuthenticator->ensureAuthenticated();

        $progress = $io->createProgressBar(count($customerOrders));
        $progress->start();

        $processed = 0;
        $processedIds = [];

        foreach ($customerOrders as $customerOrder) {
            if (!$customerOrder instanceof CustomerOrder) {
                continue;
            }

            $this->orderAllocator->process($customerOrder);

            $processedIds[] = (string) $customerOrder->getId();
            ++$processed;
            $progress->advance();
        }

        $this->flusher->flush();

        $progress->finish();
        $io->newLine(2);
        $io->success(sprintf('Processed %d customer orders.', $processed));

        if ($processed > 0 && $output->isVerbose()) {
            $io->section('Processed Order IDs');
            $io->listing($processedIds);
        }

        return Command::SUCCESS;
    }

    private function getNextCustomerOrders(int $orderCount): ?array
    {
        return $this->orders->findNextOrdersToBeProcessed($orderCount);
    }
}
