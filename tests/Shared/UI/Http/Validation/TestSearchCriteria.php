<?php

namespace App\Tests\Shared\UI\Http\Validation;

use App\Shared\Application\Search\SearchCriteria;

final class TestSearchCriteria extends SearchCriteria
{
    public ?string $startDate = null;

    public ?string $endDate = null;
}
