<?php

namespace App\Reporting\Application\Handler;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductId;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Purchasing\Domain\Model\Supplier\SupplierId;
use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Reporting\Domain\Model\SalesType\ProductSales;
use App\Reporting\Domain\Repository\ProductSalesRepository;
use App\Shared\Application\FlusherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CalculateProductSalesHandler
{
    public function __construct(
        private ProductSalesRepository $productSalesRepository,
        private PurchaseOrderItemRepository $purchaseOrderItemRepository,
        private ProductRepository $productRepository,
        private SupplierRepository $supplierRepository,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function process(string $date, bool $dryRun = false): int
    {
        $sales = $this->getPurchaseOrderItemSales($date);

        if (!$dryRun) {
            $this->removeExistingProductSales($date);
        }

        $processed = 0;
        foreach ($sales as $sale) {
            $product = $this->productRepository->get(ProductId::fromInt((int) $sale['productId']));
            $supplier = $this->supplierRepository->get(SupplierId::fromInt((int) $sale['supplierId']));

            if ($product instanceof Product) {
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

                if (!$dryRun) {
                    $this->productSalesRepository->add($productSales);
                }

                ++$processed;
            }
        }

        if (!$dryRun) {
            $this->flusher->flush();
        }

        return $processed;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPurchaseOrderItemSales(string $date): array
    {
        return $this->purchaseOrderItemRepository
            ->calculateProductSales(new \DateTime($date), new \DateTime($date)->modify('+ 1 day'));
    }

    private function removeExistingProductSales(string $date): void
    {
        $this->productSalesRepository->deleteByDate($date);
    }
}
