<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Http\Validation;

use App\Shared\Application\Search\DateRangeSearchCriteriaInterface;
use App\Shared\Application\Search\SearchCriteria;

final class TestSearchCriteria extends SearchCriteria implements DateRangeSearchCriteriaInterface
{
    public ?string $startDate = null;

    public ?string $endDate = null;

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }
}
