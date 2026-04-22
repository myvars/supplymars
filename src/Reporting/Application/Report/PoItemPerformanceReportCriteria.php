<?php

declare(strict_types=1);

namespace App\Reporting\Application\Report;

use App\Shared\Application\Search\SearchCriteria;

final class PoItemPerformanceReportCriteria extends SearchCriteria implements ReportCriteriaInterface
{
    use SalesDurationTrait;

    protected const string SORT_DEFAULT = 'profit';

    protected const string SORT_DIRECTION_DEFAULT = 'ASC';

    protected const array SORT_OPTIONS = ['profit', 'status', 'product.name', 'supplier.name'];

    protected const int LIMIT_DEFAULT = 10;
}
