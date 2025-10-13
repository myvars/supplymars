<?php

namespace App\Reporting\Application\Report;

use App\Reporting\Domain\Metric\OrderSalesMetric;
use App\Shared\Application\Search\SearchCriteria;

final class OrderSummaryReportCriteria extends SearchCriteria implements ReportCriteriaInterface
{
    use SalesDurationTrait;

    protected const string SORT_DEFAULT = OrderSalesMetric::COUNT->value;

    protected const array SORT_OPTIONS = ['orderCount', 'orderValue', 'averageOrderValue'];
}
