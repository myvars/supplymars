<?php

namespace App\Command\Installation;

use App\Entity\Product;
use App\Entity\Supplier;
use App\Entity\SupplierProduct;
use App\Service\Product\ProductGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-supplier-products',
    description: 'Create new products and product maps from a supplier',
)]
class createSupplierProductsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductGenerator $productGenerator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->note('Starting to process supplier products...');

        foreach ($this->getSuppliers() as $supplier) {
            $statistics = $this->processSupplierProducts($supplier);
            $this->reportResults($statistics, $supplier->getName(), $io);
        }

        return Command::SUCCESS;
    }

    private function processSupplierProducts(Supplier $supplier): array
    {
        $supplierProducts = $this->getSupplierProducts($supplier);
        $statistics = ['new' => 0, 'mapped' => 0, 'skipped' => 0, 'inactive' => 0];

        foreach ($supplierProducts as $supplierProduct) {
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
            $product->addSupplierProduct($supplierProduct);
            // TODO add category/subcategory mapping (see productGenerator)
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            return 'mapped';
        }

        $this->productGenerator->createFromSupplierProduct($supplierProduct);

        return 'new';
    }

    private function reportResults(array $statistics, string $supplierName, SymfonyStyle $io): void
    {
        $io->success(sprintf("Processed supplier products for '%s':", $supplierName));
        $io->listing([
            'New products created: '.$statistics['new'],
            'Products mapped: '.$statistics['mapped'],
            'Products skipped (already mapped): '.$statistics['skipped'],
            'Inactive products skipped: '.$statistics['inactive'],
        ]);
    }

    private function getSuppliers(): array
    {
        return $this->entityManager->getRepository(Supplier::class)->findBy(['isWarehouse' => false]);
    }

    private function getSupplierProducts(Supplier $supplier): array
    {
        return $this->entityManager->getRepository(SupplierProduct::class)->findBy(['supplier' => $supplier]);
    }

    public function checkMfrPartNumber(string $mfrPartNumber): ?Product
    {
        return $this->entityManager->getRepository(Product::class)->findOneBy(['mfrPartNumber' => $mfrPartNumber]);
    }
}
