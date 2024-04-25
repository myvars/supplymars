<?php

namespace App\Command;

use App\Entity\Supplier;
use App\Entity\SupplierProduct;
use App\Service\ProductGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-products',
    description: 'Create new products from a supplier products table',
)]
class ImportProductsCommand extends Command
{
    public const DEFAULT_SUPPLIER = 'Turtle Inc';

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

        if (!$supplier = $this->getSupplier()) {
            $io->error('Supplier not found');

            return Command::FAILURE;
        }

        $productCount = 0;
        $supplierProducts = $this->getSupplierProducts($supplier);
        foreach ($supplierProducts as $supplierProduct) {
            $this->productGenerator->createFromSupplierProduct($supplierProduct);
            $productCount++;
        }

        $io->success('Created ' . $productCount . ' products from ' . self::DEFAULT_SUPPLIER . ' supplier.');

        return Command::SUCCESS;
    }

    private function getSupplier(): Supplier
    {
        return $this->entityManager->getRepository(Supplier::class)->findOneBy(['name' => self::DEFAULT_SUPPLIER]);
    }

    private function getSupplierProducts(Supplier $supplier): array
    {
        return $this->entityManager->getRepository(SupplierProduct::class)->findBy(['supplier' => $supplier]);
    }
}
