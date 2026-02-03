<?php

namespace App\Purchasing\UI\Console\Setup;

use App\Purchasing\Application\Command\SupplierProduct\MapSupplierProduct;
use App\Purchasing\Application\Handler\SupplierProduct\MapSupplierProductHandler;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-warehouse-products',
    description: 'Create new products from the default supplier products table',
)]
readonly class CreateWarehouseProductsCommand
{
    public function __construct(
        private SupplierRepository $suppliers,
        private SupplierProductRepository $supplierProducts,
        private MapSupplierProductHandler $mapSupplierProductHandler,
        private DefaultUserAuthenticator $defaultUserAuthenticator,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $supplier = $this->getDefaultSupplier();
        if (!$supplier instanceof Supplier) {
            $io->error('No default supplier found');

            return Command::FAILURE;
        }

        $supplierProducts = $this->getSupplierProducts($supplier);
        if ($supplierProducts === []) {
            $io->note('No supplier products found.');

            return Command::SUCCESS;
        }

        $this->defaultUserAuthenticator->ensureAuthenticated();

        $progress = $io->createProgressBar(count($supplierProducts));
        $progress->start();

        $processed = 0;
        $processedIds = [];

        foreach ($supplierProducts as $supplierProduct) {
            if (!$supplierProduct instanceof SupplierProduct) {
                continue;
            }

            $result = ($this->mapSupplierProductHandler)(
                new MapSupplierProduct($supplierProduct->getPublicId())
            );

            $processedIds[] = $supplierProduct->getProductCode() . ' : ' . ($result->ok ? 'Mapped' : 'Skipped');

            ++$processed;
            $progress->advance();
        }

        $progress->finish();
        $io->newLine(2);
        $io->success(sprintf('Processed %d supplier products.', $processed));

        if ($output->isVerbose()) {
            $io->section('Processed Product IDs');
            $io->listing($processedIds);
        }

        return Command::SUCCESS;
    }

    private function getDefaultSupplier(): ?Supplier
    {
        return $this->suppliers->getWarehouseSupplier();
    }

    /**
     * @return array<int, SupplierProduct>
     */
    private function getSupplierProducts(Supplier $supplier): array
    {
        return $this->supplierProducts->findBySupplier($supplier);
    }
}
