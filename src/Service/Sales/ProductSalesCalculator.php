<?php

namespace App\Service\Sales;

use App\Entity\Product;
use App\Entity\ProductSales;
use App\Entity\PurchaseOrderItem;
use App\Entity\Supplier;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;

class ProductSalesCalculator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function calculateForDate(string $date): void
    {
        $this->removeExistingProductSales($date);

        $sales = $this->getPurchaseOrderItemSales($date);

        foreach ($sales as $sale) {
            $product = $this->entityManager->getRepository(Product::class)->find($sale['productId']);
            $supplier = $this->entityManager->getRepository(Supplier::class)->find($sale['supplierId']);

            if ($product) {
                $productSales = ProductSales::create(
                    $product,
                    $supplier,
                    $date,
                    $sale['salesQty'],
                    $sale['salesCost'],
                    $sale['salesValue']
                );
                $this->entityManager->persist($productSales);
            }
        }
        $this->entityManager->flush();
    }

    private function getPurchaseOrderItemSales(string $date): array
    {
        return $this->entityManager
            ->getRepository(PurchaseOrderItem::class)
            ->calculateProductSales(new DateTime($date), new DateTime($date . ' 23:59:59'));
    }

    public function getProductSalesByDate(string $date): array
    {
        return $this->entityManager->getRepository(ProductSales::class)->findBy(['dateString' => $date]);
    }

    private function removeExistingProductSales(string $date): void
    {
        foreach ($this->getProductSalesByDate($date) as $productSale) {
            $this->entityManager->remove($productSale);
        }
        $this->entityManager->flush();
    }
}