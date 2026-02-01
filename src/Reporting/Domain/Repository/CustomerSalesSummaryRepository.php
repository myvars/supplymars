<?php

namespace App\Reporting\Domain\Repository;

use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerSalesSummaryDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CustomerSalesSummaryDoctrineRepository::class)]
interface CustomerSalesSummaryRepository
{
}
