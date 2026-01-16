<?php

namespace App\Purchasing\UI\Console\Utilities;

use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\Supplier\SupplierId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:reset-supplier-stock',
    description: 'Reset supplier stock levels',
)]
readonly class resetSupplierStockCommand
{
    public const int MAX_STOCK_LEVEL = 300;

    public function __construct(
        private DefaultUserAuthenticator $defaultUserAuthenticator,
        private SupplierRepository $suppliers,
        private SupplierProductRepository $supplierProducts,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Supplier to process', name: 'supplierId')]
        string $supplierId,
    ): int {
        $io = new SymfonyStyle($input, $output);

        if (self::MAX_STOCK_LEVEL < 0) {
            $io->error('Max stock level must be greater than 0');

            return Command::FAILURE;
        }

        $supplier = $this->getSupplier($supplierId);
        if (!$supplier instanceof Supplier) {
            $io->error('No supplier found');

            return Command::FAILURE;
        }

        $this->defaultUserAuthenticator->ensureAuthenticated();

        $io->success(sprintf('Resetting stock for supplier %s', $supplier->getName()));

        $supplierProducts = $this->getSupplierProducts($supplier);
        if ($supplierProducts === []) {
            $io->note('No supplier products found.');

            return Command::SUCCESS;
        }

        $progress = $io->createProgressBar(count($supplierProducts));
        $progress->start();

        $processed = 0;
        $processedItems = [];

        foreach ($supplierProducts as $supplierProduct) {
            if (!$supplierProduct instanceof SupplierProduct) {
                continue;
            }

            $previousStock = $supplierProduct->getStock();
            $supplierProduct->updateStock(random_int(0, self::MAX_STOCK_LEVEL));

            $processedItems[] = sprintf('%s : %d (%d)',
                $supplierProduct->getProductCode(),
                $supplierProduct->getStock(),
                $previousStock
            );
            ++$processed;

            $progress->advance();
        }

        $this->flusher->flush();

        $progress->finish();
        $io->newLine(2);
        $io->success(sprintf('Processed %d items.', $processed));

        if ($processed > 0 && $output->isVerbose()) {
            $io->section('Processed product codes');
            $io->listing($processedItems);
        }

        return Command::SUCCESS;
    }

    private function getSupplier(int $supplierId): ?Supplier
    {
        return $this->suppliers->get(SupplierId::fromInt($supplierId));
    }

    private function getSupplierProducts(Supplier $supplier): array
    {
        return $this->supplierProducts->findBy(['supplier' => $supplier]);
    }
}
