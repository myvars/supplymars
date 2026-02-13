<?php

namespace App\Reporting\Domain\Repository;

use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use App\Reporting\Domain\Model\SalesType\CustomerSegmentSummary;
use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerSegmentSummaryDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CustomerSegmentSummaryDoctrineRepository::class)]
interface CustomerSegmentSummaryRepository
{
    public function add(CustomerSegmentSummary $customerSegmentSummary): void;

    public function deleteByCustomerSalesType(CustomerSalesType $customerSalesType): void;
}
