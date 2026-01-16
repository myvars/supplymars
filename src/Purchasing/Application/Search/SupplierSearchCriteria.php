<?php

namespace App\Purchasing\Application\Search;

use App\Shared\Application\Search\SearchCriteria;

final class SupplierSearchCriteria extends SearchCriteria
{
    protected const array SORT_OPTIONS = ['id', 'name', 'isActive'];
}
