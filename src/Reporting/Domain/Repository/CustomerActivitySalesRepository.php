<?php

namespace App\Reporting\Domain\Repository;

use App\Reporting\Domain\Model\SalesType\CustomerActivitySales;
use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerActivitySalesDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CustomerActivitySalesDoctrineRepository::class)]
interface CustomerActivitySalesRepository
{
    public function add(CustomerActivitySales $customerActivitySales): void;

    public function deleteByDate(string $date): void;
}
