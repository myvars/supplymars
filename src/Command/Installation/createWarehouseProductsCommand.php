<?php

namespace App\Command\Installation;

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
    name: 'app:create-warehouse-products',
    description: 'Create new products from the default supplier products table',
)]
class createWarehouseProductsCommand extends Command
{
    public const DEFAULT_SUPPLIER_NAME = 'Turtle Inc';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductGenerator $productGenerator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $supplier = $this->getSupplier();

        $productCount = $this->processSupplierProducts($supplier);
        $io->success(sprintf(
            'Created %d products from %s supplier.', $productCount, self::DEFAULT_SUPPLIER_NAME)
        );

        return Command::SUCCESS;
    }

    private function processSupplierProducts(Supplier $supplier): int
    {
        $supplierProducts = $this->getSupplierProducts($supplier);
        $productCount = 0;

        foreach ($supplierProducts as $supplierProduct) {
            $this->productGenerator->createFromSupplierProduct($supplierProduct);
            $productCount++;
        }

        return $productCount;
    }

    private function getSupplier(): Supplier
    {
        return $this->entityManager->getRepository(Supplier::class)->findOneBy(['name' => self::DEFAULT_SUPPLIER_NAME]);
    }

    private function getSupplierProducts(Supplier $supplier): array
    {
        return $this->entityManager->getRepository(SupplierProduct::class)->findBy(['supplier' => $supplier]);
    }
}
