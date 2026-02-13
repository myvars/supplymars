<?php

namespace App\Reporting\Domain\Repository;

use App\Reporting\Domain\Model\SalesType\ProductSalesSummary;
use App\Reporting\Domain\Model\SalesType\ProductSalesType;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesSummaryDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProductSalesSummaryDoctrineRepository::class)]
interface ProductSalesSummaryRepository
{
    public function add(ProductSalesSummary $productSalesSummary): void;

    public function deleteByProductSalesType(ProductSalesType $productSalesType): void;
}
