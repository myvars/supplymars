<?php

namespace App\Reporting\Application\Report;

use App\Reporting\Domain\Metric\CustomerSalesMetric;
use App\Shared\Application\Search\SearchCriteria;

final class CustomerInsightsReportCriteria extends SearchCriteria implements ReportCriteriaInterface
{
    use SalesDurationTrait;

    protected const string SORT_DEFAULT = CustomerSalesMetric::ACTIVE->value;

    protected const array SORT_OPTIONS = ['activeCustomers', 'newCustomers', 'returningCustomers'];
}
