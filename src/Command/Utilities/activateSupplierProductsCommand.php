<?php

namespace App\Command\Utilities;

use Symfony\Component\Console\Attribute\Argument;
use App\Entity\SupplierProduct;
use App\Service\OrderProcessing\SupplierUtility;
use App\Service\SupplierProduct\ChangeMappedProductStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:activate-supplier-products',
    description: 'Activate supplier products',
)]
class activateSupplierProductsCommand
{
    public function __construct(private readonly SupplierUtility $supplierUtility, private readonly EntityManagerInterface $entityManager, private readonly ChangeMappedProductStatus $changeMappedProductStatus)
    {
    }

    public function __invoke(#[Argument(name: 'itemCount', description: 'Item count to process')]
    string $itemCount, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->supplierUtility->setDefaultUser();

        $io->success('Activating supplier products');

        $supplierProducts = $this->getSupplierProducts($itemCount);

        $processedItemCount = 0;
        foreach ($supplierProducts as $supplierProduct) {
            $this->changeMappedProductStatus->toggleMappedProductStatus($supplierProduct);
            ++$processedItemCount;

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
