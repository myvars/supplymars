<?php

namespace App\Reporting\Domain\Repository;

use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerSalesDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CustomerSalesDoctrineRepository::class)]
interface CustomerSalesRepository
{
}
