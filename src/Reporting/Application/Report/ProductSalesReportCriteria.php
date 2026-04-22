<?php

declare(strict_types=1);

namespace App\Reporting\Application\Report;

use App\Reporting\Domain\Metric\ProductSalesMetric;
use App\Shared\Application\Search\SearchCriteria;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductSalesReportCriteria extends SearchCriteria implements ReportCriteriaInterface
{
    use SalesDurationTrait;

    protected const string SORT_DIRECTION_DEFAULT = 'ASC';

    protected const string SORT_DEFAULT = ProductSalesMetric::QTY->value;

    protected const array SORT_OPTIONS = ['salesQty', 'salesCost', 'salesValue', 'salesProfit', 'salesMargin'];

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Product Id', min: 1, max: 1000000)]
    public ?int $productId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Category Id', min: 1, max: 1000000)]
    public ?int $categoryId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Subcategory Id', min: 1, max: 1000000)]
    public ?int $subcategoryId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Manufacturer Id', min: 1, max: 1000000)]
    public ?int $manufacturerId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Supplier Id', min: 1, max: 1000000)]
    public ?int $supplierId = null;
}
