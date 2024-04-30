<?php

namespace App\Service\Product;

use App\Entity\Product;
use App\Entity\SupplierProduct;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class ActiveSourceCalculator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository
    ) {
    }

    public function recalculateActiveSource(
        Product $product,
        bool $flush = true
    ): void {
        $supplierProducts = $product->getSupplierProducts();

        foreach ($supplierProducts as $supplierProduct) {
            if ($supplierProduct->getSupplier()->IsisActive()
                && $supplierProduct->IsisActive()
                && $supplierProduct->getStock() > 0
                && $supplierProduct->getCost() > 0) {
                if (!isset($activeSource)) {
                    $activeSource = $supplierProduct;
                    continue;
                }

                if ($supplierProduct->getCost() === $activeSource->getCost()
                    && $supplierProduct->getStock() <= $activeSource->getStock()
                ) {
                    continue;
                }

                if ($supplierProduct->getCost() < $activeSource->getCost()) {
                    $activeSource = $supplierProduct;
                }
            }
        }

        if (isset($activeSource)) {
            $this->updateProductStock($product, $activeSource);
        } else {
            $this->removeActiveProductSource($product);
        }

        if ($flush) {
            $this->flush();
        }
    }

    /**
     * @param Product[] $products
     */
    public function recalculateActiveSourceFromArray(array $products): void {
        foreach ($products as $product) {
            $this->recalculateActiveSource($product, false);
        }
        $this->flush();
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function getProductFromActiveSource($supplierProduct): ?Product
    {
        return $this->productRepository->findOneBy(['activeProductSource' => $supplierProduct]);
    }

    private function updateProductStock(Product $product, SupplierProduct $activeSource): void
    {
        $product->setCost($activeSource->getCost());
        $product->setLeadTimeDays($activeSource->getLeadTimeDays());
        $product->setStock($activeSource->getStock());
        $product->setActiveProductSource($activeSource);
    }

    private function removeActiveProductSource(Product $product): void
    {
        $product->setActiveProductSource(null);
    }

    public function removeMappedProduct(SupplierProduct $supplierProduct): void
    {
        $supplierProduct->setProduct(null);
        $this->entityManager->persist($supplierProduct);
        $this->flush();
    }

    public function toggleStatus(SupplierProduct $supplierProduct): void
    {
        $supplierProduct->setIsActive(!$supplierProduct->IsisActive());
        $this->entityManager->persist($supplierProduct);
        $this->flush();
    }
}
