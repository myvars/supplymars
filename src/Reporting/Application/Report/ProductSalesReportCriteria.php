<?php

namespace App\Reporting\Application\Report;

use App\Reporting\Domain\Metric\ProductSalesMetric;
use App\Reporting\Domain\Metric\SalesType;
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

    public function getSingleSalesType(): ?array
    {
        $identifiers = [
            'product' => $this->productId,
            'category' => $this->categoryId,
            'subcategory' => $this->subcategoryId,
            'manufacturer' => $this->manufacturerId,
            'supplier' => $this->supplierId,
        ];

        // Filter to get only non-null values
        $nonEmptyIdentifiers = array_filter($identifiers, fn (?int $value): bool => !is_null($value));

        if ([] === $nonEmptyIdentifiers) {
            return [
                'salesType' => SalesType::ALL,
                'salesTypeId' => 1,
            ];
        }

        if (1 === count($nonEmptyIdentifiers)) {
            $salesType = array_key_first($nonEmptyIdentifiers);

            return [
                'salesType' => SalesType::from($salesType),
                'salesTypeId' => $nonEmptyIdentifiers[$salesType],
            ];
        }

        return null;
    }
}
