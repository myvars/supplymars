<?php

namespace App\Reporting\Domain\Repository;

use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerActivitySalesDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CustomerActivitySalesDoctrineRepository::class)]
interface CustomerActivitySalesRepository
{
}
