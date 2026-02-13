<?php

namespace App\Reporting\Application\Handler;

use App\Catalog\Infrastructure\Persistence\Doctrine\ProductDoctrineRepository;
use App\Purchasing\Infrastructure\Persistence\Doctrine\PurchaseOrderItemDoctrineRepository;
use App\Purchasing\Infrastructure\Persistence\Doctrine\SupplierDoctrineRepository;
use App\Reporting\Domain\Model\SalesType\ProductSales;
use App\Reporting\Domain\Repository\ProductSalesRepository;
use App\Shared\Application\FlusherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CalculateProductSalesHandler
{
    public function __construct(
        private ProductSalesRepository $productSalesRepository,
        private PurchaseOrderItemDoctrineRepository $purchaseOrderItemRepository,
        private ProductDoctrineRepository $productRepository,
        private SupplierDoctrineRepository $supplierRepository,
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
            $product = $this->productRepository->find($sale['productId']);
            $supplier = $this->supplierRepository->find($sale['supplierId']);

            if ($product !== null) {
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
