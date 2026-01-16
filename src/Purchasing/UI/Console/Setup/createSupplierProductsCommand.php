<?php

namespace App\Purchasing\UI\Console\Setup;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Purchasing\Application\Command\SupplierProduct\MapSupplierProduct;
use App\Purchasing\Application\Handler\SupplierProduct\MapSupplierProductHandler;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-supplier-products',
    description: 'Create new products and product maps from a supplier',
)]
readonly class createSupplierProductsCommand
{
    public function __construct(
        private SupplierRepository $suppliers,
        private SupplierProductRepository $supplierProducts,
        private ProductRepository $products,
        private MapSupplierProductHandler $mapSupplierProductHandler,
        private DefaultUserAuthenticator $defaultUserAuthenticator,
        private MarkupCalculator $markupCalculator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $io->section('Processing supplier products');

        $suppliers = $this->getSuppliers();
        if ($suppliers === []) {
            $io->error('No suppliers found');

            return Command::FAILURE;
        }

        $this->defaultUserAuthenticator->ensureAuthenticated();

        foreach ($suppliers as $supplier) {
            if (!$supplier instanceof Supplier) {
                continue;
            }

            $statistics = $this->processSupplierProducts($supplier);
            $this->reportResults($statistics, $supplier->getName(), $io);
        }

        return Command::SUCCESS;
    }

    private function processSupplierProducts(Supplier $supplier): array
    {
        $statistics = ['new' => 0, 'mapped' => 0, 'skipped' => 0, 'inactive' => 0];

        $supplierProducts = $this->getSupplierProducts($supplier);
        foreach ($supplierProducts as $supplierProduct) {
            if (!$supplierProduct instanceof SupplierProduct) {
                continue;
            }

            $result = $this->processEachSupplierProduct($supplierProduct);
            ++$statistics[$result];
        }

        return $statistics;
    }

    private function processEachSupplierProduct(SupplierProduct $supplierProduct): string
    {
        if (!$supplierProduct->isActive()) {
            return 'inactive';
        }

        if ($supplierProduct->getProduct() instanceof Product) {
            return 'skipped';
        }

        $product = $this->checkMfrPartNumber($supplierProduct->getMfrPartNumber());
        if ($product instanceof Product) {
            $product->addSupplierProduct($this->markupCalculator, $supplierProduct);

            $this->flusher->flush();

            return 'mapped';
        }

        $result = ($this->mapSupplierProductHandler)(
            new MapSupplierProduct($supplierProduct->getPublicId())
        );

        if (!$result->ok) {
            return 'skipped';
        }

        return 'new';
    }

    private function reportResults(array $statistics, string $supplierName, SymfonyStyle $io): void
    {
        $io->success(sprintf("Processed supplier products for '%s':", $supplierName));
        $io->listing([
            'New products created: ' . $statistics['new'],
            'Products mapped: ' . $statistics['mapped'],
            'Products skipped (already mapped): ' . $statistics['skipped'],
            'Inactive products skipped: ' . $statistics['inactive'],
        ]);
    }

    private function getSuppliers(): array
    {
        return $this->suppliers->findBy(['isWarehouse' => false]);
    }

    private function getSupplierProducts(Supplier $supplier): array
    {
        return $this->supplierProducts->findBy(['supplier' => $supplier]);
    }

    public function checkMfrPartNumber(string $mfrPartNumber): ?Product
    {
        return $this->products->findOneBy(['mfrPartNumber' => $mfrPartNumber]);
    }
}
