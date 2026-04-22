<?php

declare(strict_types=1);

namespace App\Reporting\Application\Report;

use App\Shared\Application\Search\SearchCriteria;

final class OverdueOrderReportCriteria extends SearchCriteria implements ReportCriteriaInterface
{
    use SalesDurationTrait;

    protected const string SORT_DEFAULT = 'dueDate';

    protected const array SORT_OPTIONS = ['id', 'dueDate', 'customer.fullName', 'totalPrice', 'status'];

    protected const int LIMIT_DEFAULT = 10;
}
