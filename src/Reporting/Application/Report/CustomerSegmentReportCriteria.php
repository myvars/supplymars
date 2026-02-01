<?php

namespace App\Reporting\Application\Report;

use App\Shared\Application\Search\SearchCriteria;

final class CustomerSegmentReportCriteria extends SearchCriteria implements ReportCriteriaInterface
{
    use SalesDurationTrait;

    protected const string SORT_DEFAULT = 'orderValue';

    protected const array SORT_OPTIONS = ['customerCount', 'orderCount', 'orderValue', 'averageOrderValue'];
}
