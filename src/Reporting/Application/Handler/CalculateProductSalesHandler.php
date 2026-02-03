<?php

namespace App\Reporting\Application\Handler;

use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Reporting\Domain\Model\SalesType\ProductSales;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CalculateProductSalesHandler
{
    public function __construct(
        private EntityManagerInterface $em,
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
            $product = $this->em->getRepository(Product::class)->find($sale['productId']);
            $supplier = $this->em->getRepository(Supplier::class)->find($sale['supplierId']);

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
                    $this->em->persist($productSales);
                }

                ++$processed;
            }
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        return $processed;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPurchaseOrderItemSales(string $date): array
    {
        return $this->em
            ->getRepository(PurchaseOrderItem::class)
            ->calculateProductSales(new \DateTime($date), new \DateTime($date)->modify('+ 1 day'));
    }

    private function removeExistingProductSales(string $date): void
    {
        $this->em->getRepository(ProductSales::class)
            ->deleteByDate($date);
    }
}
