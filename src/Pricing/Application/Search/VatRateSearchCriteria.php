<?php

namespace App\Pricing\Application\Search;

use App\Shared\Application\Search\SearchCriteria;

final class VatRateSearchCriteria extends SearchCriteria
{
    protected const array SORT_OPTIONS = ['id', 'name'];
}
