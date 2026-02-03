<?php

namespace App\Purchasing\UI\Console;

use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\Supplier\SupplierId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-supplier-stock',
    description: 'Update supplier stock levels',
)]
readonly class UpdateSupplierStockCommand
{
    public const int COST_VARIANCE_PERCENT = 10;

    public const int STOCK_VARIANCE_PERCENT = 10;

    public const int STOCK_REPLENISH_LEVEL = 20;

    public function __construct(
        private SupplierRepository $suppliers,
        private SupplierProductRepository $supplierProducts,
        private DefaultUserAuthenticator $defaultUserAuthenticator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Number of supplier products to process')]
        int $productCount = 50,
        #[Option(description: 'Run without persisting changes')]
        bool $dryRun = false,
        #[Option(description: 'Target a specific supplier by ID')]
        ?int $supplier = null,
    ): int {
        $io = new SymfonyStyle($input, $output);

        if ($productCount < 1) {
            $io->error('Product count must be > 0.');

            return Command::INVALID;
        }

        $supplierEntity = $this->resolveSupplier($supplier);
        if (!$supplierEntity instanceof Supplier) {
            $io->error($supplier !== null ? 'Supplier not found: ' . $supplier : 'No supplier found');

            return Command::FAILURE;
        }

        $this->defaultUserAuthenticator->ensureAuthenticated();

        $io->section(sprintf(
            '%sProcessing stock for up to %d items from supplier %s',
            $dryRun ? '[DRY RUN] ' : '',
            $productCount,
            $supplierEntity->getName()
        ));

        $supplierProducts = $this->getRandomSupplierProducts($supplierEntity, $productCount);
        if ($supplierProducts === []) {
            $io->note('No supplier products found.');

            return Command::SUCCESS;
        }

        $progress = $io->createProgressBar(count($supplierProducts));
        $progress->start();

        $processed = 0;
        $stockIncreased = 0;
        $stockDecreased = 0;
        $processedItems = [];

        foreach ($supplierProducts as $supplierProduct) {
            if (!$supplierProduct instanceof SupplierProduct) {
                continue;
            }

            $previousStock = $supplierProduct->getStock();
            $previousCost = $supplierProduct->getCost();

            if (!$dryRun) {
                $this->realWorldStockLevelSimulator($supplierProduct);
            } else {
                // Simulate what would happen for dry-run reporting
                $wouldReplenish = $supplierProduct->getStock() <= self::STOCK_REPLENISH_LEVEL;
                if ($wouldReplenish) {
                    ++$stockIncreased;
                } else {
                    ++$stockDecreased;
                }
            }

            if (!$dryRun) {
                if ($supplierProduct->getStock() > $previousStock) {
                    ++$stockIncreased;
                } elseif ($supplierProduct->getStock() < $previousStock) {
                    ++$stockDecreased;
                }
            }

            $processedItems[] = sprintf(
                '%s : stock %d (%d) : cost %s (%s)',
                $supplierProduct->getProductCode(),
                $dryRun ? $previousStock : $supplierProduct->getStock(),
                $previousStock,
                $dryRun ? $previousCost : $supplierProduct->getCost(),
                $previousCost
            );
            ++$processed;

            $progress->advance();
        }

        if (!$dryRun) {
            $this->flusher->flush();
        }

        $progress->finish();
        $io->newLine(2);

        $io->success(sprintf(
            '%sProcessed %d supplier products: %d stock increased, %d stock decreased.',
            $dryRun ? '[DRY RUN] ' : '',
            $processed,
            $stockIncreased,
            $stockDecreased
        ));

        if ($output->isVerbose()) {
            $io->section('Processed Supplier products:');
            $io->listing($processedItems);
        }

        return Command::SUCCESS;
    }

    private function resolveSupplier(?int $supplierId): ?Supplier
    {
        if ($supplierId !== null) {
            return $this->suppliers->get(SupplierId::fromInt($supplierId));
        }

        return $this->suppliers->getRandomSupplier();
    }

    /**
     * @return array<int, SupplierProduct>
     */
    private function getRandomSupplierProducts(Supplier $supplier, int $itemCount): array
    {
        return $this->supplierProducts->findRandomSupplierProducts($supplier, $itemCount);
    }

    private function realWorldStockLevelSimulator(SupplierProduct $supplierProduct): void
    {
        // Simulate real world stock level changes
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
        $stockPercent = bcdiv((string) $supplierProduct->getStock(), (string) self::STOCK_VARIANCE_PERCENT, 2);
        $stockChange = random_int(0, (int) ceil((float) $stockPercent));
        $supplierProduct->updateStock($supplierProduct->getStock() - $stockChange);
    }

    public function increaseStock(SupplierProduct $supplierProduct): void
    {
        // allow stock level to increase by random amount up to 100
        $supplierProduct->updateStock($supplierProduct->getStock() + random_int(0, 100));
    }

    public function changeCost(SupplierProduct $supplierProduct): void
    {
        $cost = $supplierProduct->getCost() ?? '0.00';

        // check cost is greater than 0
        if (1 !== bccomp($cost, '0', 2)) {
            return;
        }

        $percentCost = bcdiv($cost, (string) self::COST_VARIANCE_PERCENT, 2);
        $randomCost = bcdiv((string) random_int(0, (int) bcmul($percentCost, '100', 0)), '100', 2);
        $randomCost = 0 === random_int(0, 1) ? $randomCost : bcsub('0', $randomCost, 2);

        $supplierProduct->updateCost(bcadd($cost, $randomCost, 2));
    }
}
