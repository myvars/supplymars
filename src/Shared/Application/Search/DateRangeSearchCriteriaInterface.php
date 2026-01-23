<?php

namespace App\Shared\Application\Search;

interface DateRangeSearchCriteriaInterface
{
    public function getStartDate(): ?string;

    public function getEndDate(): ?string;
}
