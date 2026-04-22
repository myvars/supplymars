<?php

declare(strict_types=1);

namespace App\Reporting\Application\Report;

use App\Reporting\Domain\Metric\SalesDuration;

interface ReportCriteriaInterface
{
    public function getDuration(): SalesDuration;
}
