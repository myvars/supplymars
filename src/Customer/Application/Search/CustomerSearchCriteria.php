<?php

namespace App\Customer\Application\Search;

use App\Shared\Application\Search\SearchCriteria;

final class CustomerSearchCriteria extends SearchCriteria
{
    protected const array SORT_OPTIONS = ['id', 'fullName', 'email', 'isVerified'];
}
