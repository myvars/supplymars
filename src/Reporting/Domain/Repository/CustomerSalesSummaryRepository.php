<?php

namespace App\Reporting\Domain\Repository;

use App\Reporting\Domain\Model\SalesType\CustomerSalesSummary;
use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerSalesSummaryDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CustomerSalesSummaryDoctrineRepository::class)]
interface CustomerSalesSummaryRepository
{
    public function add(CustomerSalesSummary $customerSalesSummary): void;

    public function deleteByCustomerSalesType(CustomerSalesType $customerSalesType): void;
}
