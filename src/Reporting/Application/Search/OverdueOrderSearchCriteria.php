<?php

namespace App\Reporting\Application\Search;

use App\Reporting\Application\Report\ReportCriteriaInterface;
use App\Reporting\Application\Report\SalesDurationTrait;
use App\Shared\Application\Search\SearchCriteria;

final class OverdueOrderSearchCriteria extends SearchCriteria implements ReportCriteriaInterface
{
    use SalesDurationTrait;

    protected const string SORT_DEFAULT = 'dueDate';

    protected const array SORT_OPTIONS = ['id', 'dueDate', 'customer.fullName', 'totalPrice', 'status'];
    protected const int LIMIT_DEFAULT = 10;
}
