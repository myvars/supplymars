<?php

namespace App\Reporting\Domain\Repository;

use App\Reporting\Domain\Model\SalesType\CustomerSales;
use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerSalesDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CustomerSalesDoctrineRepository::class)]
interface CustomerSalesRepository
{
    public function add(CustomerSales $customerSales): void;

    public function deleteByDate(string $date): void;
}
