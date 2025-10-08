<?php

namespace App\Command\Utilities;

use App\Entity\Product;
use App\Service\OrderProcessing\SupplierUtility;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-products',
    description: 'Resync product name, description, categories, subcategories, with suppliers',
)]
class syncProductsCommand
{
    public function __construct(private readonly SupplierUtility $supplierUtility, private readonly EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(#[Argument(name: 'minProductId', description: 'min product id')]
        string $minProductId, #[Argument(name: 'maxProductId', description: 'max product id')]
        string $maxProductId, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->supplierUtility->setDefaultUser();

        $products = $this->getProducts($minProductId, $maxProductId);

        $productsCount = 0;
        foreach ($products as $product) {
            foreach ($product->getSupplierProducts() as $supplierProduct) {
                if ($supplierProduct->getName() !== $product->getName()) {
                    $supplierProduct->setName($product->getName());
                }

                if ($supplierProduct->getSupplierCategory()->getname() !== $product->getCategory()->getName()) {
                    $supplierProduct->getSupplierCategory()->setName($product->getCategory()->getName());
                }

                if ($supplierProduct->getSupplierSubcategory()->getname() !== $product->getSubcategory()->getName()) {
                    $supplierProduct->getSupplierSubcategory()->setName($product->getSubcategory()->getName());
                }
            }

            ++$productsCount;

            $io->note(sprintf('Product %05d updated', $product->getId()));

            $this->entityManager->flush();
        }

        $io->success(sprintf('Processed %d products', $productsCount));

        return Command::SUCCESS;
    }

    private function getProducts(int $minProductId, int $maxProductId): array
    {
        return $this->entityManager->getRepository(Product::class)->findBy(['id' => range($minProductId, $maxProductId)]);
    }
}
