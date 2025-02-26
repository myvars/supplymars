<?php

namespace App\Command\InventoryProcessing;

use App\Entity\Supplier;
use App\Entity\SupplierProduct;
use App\Service\OrderProcessing\SupplierUtility;
use App\Service\Utility\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-supplier-stock',
    description: 'Update supplier stock levels',
)]
class updateSupplierStockCommand extends Command
{
    public const int COST_VARIANCE_PERCENT = 10;

    public const int STOCK_VARIANCE_PERCENT = 10;

    public const int STOCK_REPLENISH_LEVEL = 20;

    public function __construct(
        private readonly SupplierUtility $supplierUtility,
        private readonly EntityManagerInterface $entityManager,
        private readonly DomainEventDispatcher $domainEventDispatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('itemCount', InputArgument::REQUIRED, 'Item count to process');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $itemCount = $input->getArgument('itemCount');

        if (self::COST_VARIANCE_PERCENT <= 0) {
            $io->error('Cost variance percent must be greater than 0');

            return Command::FAILURE;
        }

        if (self::STOCK_VARIANCE_PERCENT <= 0) {
            $io->error('Stock variance percent must be greater than 0');

            return Command::FAILURE;
        }

        $supplier = $this->supplierUtility->getRandomSupplier();

        if (!$supplier instanceof Supplier) {
            $io->error('No supplier found');

            return Command::FAILURE;
        }

        $this->supplierUtility->setDefaultUser();

        $io->success(sprintf('Processing stock for supplier %s', $supplier->getName()));

        $supplierProducts = $this->getRandomSupplierProducts($supplier, $itemCount);

        $processedItemCount = 0;
        foreach ($supplierProducts as $supplierProduct) {
            $previousStock = $supplierProduct->getStock();
            $previousCost = $supplierProduct->getCost();

            $this->realWorldStockLevelSimulator($supplierProduct);
            ++$processedItemCount;

            $this->entityManager->flush();

            $io->note(sprintf('Updating product %s : stock %s (%s), cost £%s (£%s)',
                $supplierProduct->getProductCode(),
                $supplierProduct->getStock(),
                $previousStock,
                $supplierProduct->getCost(),
                $previousCost)
            );
        }

        $io->success(sprintf('Processed %d items', $processedItemCount));

        return Command::SUCCESS;
    }

    private function getRandomSupplierProducts(Supplier $supplier, int $itemCount): ?array
    {
        return $this->entityManager->getRepository(SupplierProduct::class)
            ->findRandomSupplierProducts($supplier, $itemCount);
    }

    private function realWorldStockLevelSimulator(SupplierProduct $supplierProduct): void
    {
        // Simulate real world stock level changes

        // TODO: Add run rate logic to replenish stock
        if ($supplierProduct->getStock() <= self::STOCK_REPLENISH_LEVEL) {
            $this->replenishStock($supplierProduct);

            return;
        }

        $this->decreaseStock($supplierProduct);
    }

    private function replenishStock(SupplierProduct $supplierProduct): void
    {
        $this->increaseStock($supplierProduct);
        $this->changeCost($supplierProduct);
    }

    private function decreaseStock(SupplierProduct $supplierProduct): void
    {
        if (0 === $supplierProduct->getStock()) {
            return;
        }

        // allow stock level to decrease by up to 10% of current stock level
        $stockPercent = bcdiv($supplierProduct->getStock(), self::STOCK_VARIANCE_PERCENT, 2);
        $stockChange = random_int(0, ceil($stockPercent));
        $supplierProduct->setStock($supplierProduct->getStock() - $stockChange);

        $this->domainEventDispatcher->dispatchProviderEvents($supplierProduct);
    }

    public function increaseStock(SupplierProduct $supplierProduct): void
    {
        // allow stock level to increase by random amount up to 100
        $supplierProduct->setStock($supplierProduct->getStock() + random_int(0, 100));

        $this->domainEventDispatcher->dispatchProviderEvents($supplierProduct);
    }

    public function changeCost(SupplierProduct $supplierProduct): void
    {
        // check cost is greater than 0
        if (1 !== bccomp((string) $supplierProduct->getCost(), '0', 2)) {
            return;
        }

        $percentCost = bcdiv((string) $supplierProduct->getCost(), self::COST_VARIANCE_PERCENT, 2);
        $randomCost = bcdiv(random_int(0, bcmul($percentCost, 100, 0)), 100, 2);
        $randomCost = 0 === random_int(0, 1) ? $randomCost : -$randomCost;

        $supplierProduct->setCost(bcadd((string) $supplierProduct->getCost(), $randomCost, 2));

        $this->domainEventDispatcher->dispatchProviderEvents($supplierProduct);
    }
}
