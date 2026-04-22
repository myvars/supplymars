<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Repository;

use App\Reporting\Domain\Model\SalesType\ProductSales;
use App\Reporting\Domain\Model\SalesType\ProductSalesType;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProductSalesDoctrineRepository::class)]
interface ProductSalesRepository
{
    public function add(ProductSales $productSales): void;

    public function deleteByDate(string $date): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findProductSalesSummary(ProductSalesType $productSalesType): array;
}
