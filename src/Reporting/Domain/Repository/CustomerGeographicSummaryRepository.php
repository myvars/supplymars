<?php

namespace App\Reporting\Domain\Repository;

use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerGeographicSummaryDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CustomerGeographicSummaryDoctrineRepository::class)]
interface CustomerGeographicSummaryRepository
{
}
