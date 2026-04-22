<?php

declare(strict_types=1);

namespace App\Shared\Application\Search;

interface DateRangeSearchCriteriaInterface
{
    public function getStartDate(): ?string;

    public function getEndDate(): ?string;
}
