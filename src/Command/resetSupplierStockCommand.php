<?php

namespace App\Command;

use App\Entity\Supplier;
use App\Entity\SupplierProduct;
use App\Service\OrderProcessing\SupplierUtility;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:reset-supplier-stock',
    description: 'Reset supplier stock levels',
)]
class resetSupplierStockCommand extends Command
{
    public const MAX_STOCK_LEVEL = 300;

    public function __construct(
        private readonly SupplierUtility $supplierUtility,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('supplierId', InputArgument::REQUIRED, 'Supplier to process');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $supplierId = $input->getArgument('supplierId');

        if (self::MAX_STOCK_LEVEL <0 ) {
            $io->error('Max stock level must be greater than 0');

            return Command::FAILURE;
        }

        $supplier = $this->getSupplier($supplierId);

        if (!$supplier instanceof Supplier) {
            $io->error('No supplier found');

            return Command::FAILURE;
        }

        $this->supplierUtility->setDefaultUser();

        $io->success(sprintf('Processing stock for supplier %s', $supplier->getName()));

        $supplierProducts = $this->getSupplierProducts($supplier);

        $processedItemCount = 0;
        foreach ($supplierProducts as $supplierProduct) {
            $previousStock = $supplierProduct->getStock();
            $supplierProduct->setStock(random_int(0, self::MAX_STOCK_LEVEL));
            $processedItemCount++;

            $this->entityManager->flush();

            $io->note(sprintf('Updating product %s : stock %s (%s)',
                $supplierProduct->getProductCode() ,
                $supplierProduct->getStock(),
                $previousStock)
            );
        }

        $io->success(sprintf('Processed %d items', $processedItemCount));

        return Command::SUCCESS;
    }

    private function getSupplier(int $supplierId): ?Supplier
    {
        return $this->entityManager->getRepository(Supplier::class)->find($supplierId);
    }

    private function getSupplierProducts(Supplier $supplier): ?array
    {
        return $this->entityManager->getRepository(SupplierProduct::class)->findBy(['supplier' => $supplier]);
    }
}