<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Repository;

use App\Reporting\Domain\Model\SalesType\CustomerGeographicSummary;
use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerGeographicSummaryDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CustomerGeographicSummaryDoctrineRepository::class)]
interface CustomerGeographicSummaryRepository
{
    public function add(CustomerGeographicSummary $customerGeographicSummary): void;

    public function deleteByCustomerSalesType(CustomerSalesType $customerSalesType): void;
}
