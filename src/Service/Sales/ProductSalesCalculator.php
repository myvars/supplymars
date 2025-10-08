<?php

namespace App\Service\Sales;

use App\Entity\Product;
use App\Entity\ProductSales;
use App\Entity\PurchaseOrderItem;
use App\Entity\Supplier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductSalesCalculator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function process(string $date): void
    {
        $sales = $this->getPurchaseOrderItemSales($date);

        $this->removeExistingProductSales($date);

        foreach ($sales as $sale) {
            $product = $this->entityManager->getRepository(Product::class)->find($sale['productId']);
            $supplier = $this->entityManager->getRepository(Supplier::class)->find($sale['supplierId']);

            if (null !== $product) {
                $productSales = ProductSales::create(
                    $product,
                    $supplier,
                    $date,
                    $sale['salesQty'],
                    $sale['salesCost'],
                    $sale['salesValue']
                );

                $errors = $this->validator->validate($productSales);
                if (count($errors) > 0) {
                    throw new \InvalidArgumentException((string) $errors);
                }

                $this->entityManager->persist($productSales);
            }
        }

        $this->entityManager->flush();
    }

    private function getPurchaseOrderItemSales(string $date): array
    {
        return $this->entityManager
            ->getRepository(PurchaseOrderItem::class)
            ->calculateProductSales(new \DateTime($date), new \DateTime($date)->modify('+ 1 day'));
    }

    private function removeExistingProductSales(string $date): void
    {
        $this->entityManager->getRepository(ProductSales::class)
            ->deleteByDate($date);
    }
}
