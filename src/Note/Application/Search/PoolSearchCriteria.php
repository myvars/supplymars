<?php

namespace App\Note\Application\Search;

use App\Shared\Application\Search\SearchCriteria;

final class PoolSearchCriteria extends SearchCriteria
{
    protected const array SORT_OPTIONS = ['id', 'name', 'isActive'];
}
