<?php

namespace App\Reporting\Domain\Repository;

use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerSegmentSummaryDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CustomerSegmentSummaryDoctrineRepository::class)]
interface CustomerSegmentSummaryRepository
{
}
