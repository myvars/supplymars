<?php

namespace App\Command\Utilities;

use App\Entity\SupplierProduct;
use App\Service\OrderProcessing\SupplierUtility;
use App\Service\SupplierProduct\ChangeMappedProductStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:activate-supplier-products',
    description: 'Activate supplier products',
)]
class activateSupplierProductsCommand extends Command
{
    public function __construct(
        private readonly SupplierUtility $supplierUtility,
        private readonly EntityManagerInterface $entityManager,
        private readonly ChangeMappedProductStatus $changeMappedProductStatus
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

        $this->supplierUtility->setDefaultUser();

        $io->success('Activating supplier products');

        $supplierProducts = $this->getSupplierProducts($itemCount);

        $processedItemCount = 0;
        foreach ($supplierProducts as $supplierProduct) {

            $this->changeMappedProductStatus->toggleMappedProductStatus($supplierProduct);
            $processedItemCount++;

            $io->note(sprintf('Activating Supplier: %s, Supplier Product ID: %d, Mapped Product ID: %d',
                $supplierProduct->getSupplier()->getName(),
                $supplierProduct->getId(),
                $supplierProduct->getProduct()->getId()
            ));
        }

        $io->success(sprintf('Processed %d Items', $processedItemCount));

        return Command::SUCCESS;
    }

    public function getSupplierProducts(int $count): array
    {
        return $this->entityManager->getRepository(SupplierProduct::class)
            ->findBy(['isActive' => false], null, $count);
    }
}