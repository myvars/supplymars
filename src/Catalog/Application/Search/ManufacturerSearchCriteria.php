<?php

declare(strict_types=1);

namespace App\Catalog\Application\Search;

use App\Shared\Application\Search\SearchCriteria;

final class ManufacturerSearchCriteria extends SearchCriteria
{
    protected const array SORT_OPTIONS = ['id', 'name', 'isActive'];
}
