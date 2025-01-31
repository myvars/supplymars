<?php

namespace App\Command\Utilities;

use App\Entity\Product;
use App\Service\OrderProcessing\SupplierUtility;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-products',
    description: 'Resync product name, description, categories, subcategories, with suppliers',
)]
class syncProductsCommand extends Command
{
    public function __construct(
        private readonly SupplierUtility $supplierUtility,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('minProductId', InputArgument::REQUIRED, 'min product id');
        $this->addArgument('maxProductId', InputArgument::REQUIRED, 'max product id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $minProductId = $input->getArgument('minProductId');
        $maxProductId = $input->getArgument('maxProductId');


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

            $productsCount++;

            $io->note(sprintf('Product %05d updated', $product->getId()));

            $this->entityManager->flush();
        }

        $io->success(sprintf('Processed %d products', $productsCount));

        return Command::SUCCESS;
    }

    private function getProducts(int $minProductId, int $maxProductId): ?array
    {
        return $this->entityManager->getRepository(Product::class)->findBy(['id' => range($minProductId, $maxProductId)]);
    }
}