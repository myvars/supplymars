<?php

namespace App\DTO;

use App\Enum\SalesDuration;
use App\Enum\SalesType;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductSalesFilterDto
{
    public const TEMPLATE = 'common/sales_filter.html.twig';

    public const SORT_DEFAULT = 'salesQty';

    public const SORT_OPTIONS = ['salesQty', 'salesValue', 'salesProfit'];

    public const SORT_DIRECTION_DEFAULT = 'DESC';

    public const LIMIT_DEFAULT = 10;

    private ?string $queryString = null;

    private string $sort = self::SORT_DEFAULT;

    private SalesDuration $duration = SalesDuration::LAST_30;

    private ?string $sortDirection = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Product Id', min: 1, max: 1000000)]
    private ?int $productId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Category Id', min: 1, max: 1000000)]
    private ?int $categoryId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Subcategory Id', min: 1, max: 1000000)]
    private ?int $subcategoryId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Manufacturer Id', min: 1, max: 1000000)]
    private ?int $manufacturerId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Supplier Id', min: 1, max: 1000000)]
    private ?int $supplierId = null;

    #[Assert\Date]
    private ?string $startDate = null;

    #[Assert\Date]
    private ?string $endDate = null;


    public function getSalesFilterParams(): array
    {
        $salesFilterParams = [
            'sort' => $this->sort,
            'sortDirection' => $this->sortDirection,
            'duration' => $this->duration->value,
            'limit' => self::LIMIT_DEFAULT,
            'productId' => $this->productId,
            'categoryId' => $this->categoryId,
            'subcategoryId' => $this->subcategoryId,
            'manufacturerId' => $this->manufacturerId,
            'supplierId' => $this->supplierId,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ];

        if (array_filter($salesFilterParams)) {
            $salesFilterParams['filter'] = 'on';
        }

        return $salesFilterParams;
    }

    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    public function setQueryString(?string $queryString): ProductSalesFilterDto
    {
        $this->queryString = $queryString;

        return $this;
    }

    public function getSort(): ?string
    {
        return $this->sort;
    }

    public function setSort(?string $sort): ProductSalesFilterDto
    {
        if (!in_array($sort, self::SORT_OPTIONS)) {
            $sort = self::SORT_DEFAULT;
        }

        $this->sort = $sort;

        return $this;
    }

    public function getSortDirection(): ?string
    {
        return $this->sortDirection;
    }

    public function setSortDirection(?string $sortDirection): ProductSalesFilterDto
    {
        if (!in_array(strtoupper((string) $sortDirection), ['ASC', 'DESC'])) {
            $sortDirection = strtolower(self::SORT_DIRECTION_DEFAULT);
        }

        $this->sortDirection = strtolower((string) $sortDirection);

        return $this;
    }

    public function getDuration(): ?SalesDuration
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): ProductSalesFilterDto
    {
        if (!SalesDuration::isValid($duration)) {
            $duration = SalesDuration::default()->value;
        }

        $this->duration = SalesDuration::from($duration);

        return $this;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(?int $productId): ProductSalesFilterDto
    {
        $this->productId = $productId;

        return $this;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function setCategoryId(?int $categoryId): ProductSalesFilterDto
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function getSubcategoryId(): ?int
    {
        return $this->subcategoryId;
    }

    public function setSubcategoryId(?int $subcategoryId): ProductSalesFilterDto
    {
        $this->subcategoryId = $subcategoryId;

        return $this;
    }

    public function getManufacturerId(): ?int
    {
        return $this->manufacturerId;
    }

    public function setManufacturerId(?int $manufacturerId): ProductSalesFilterDto
    {
        $this->manufacturerId = $manufacturerId;

        return $this;
    }

    public function getSupplierId(): ?int
    {
        return $this->supplierId;
    }

    public function setSupplierId(?int $supplierId): ProductSalesFilterDto
    {
        $this->supplierId = $supplierId;

        return $this;
    }

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function setStartDate(?string $startDate): ProductSalesFilterDto
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function setEndDate(?string $endDate): ProductSalesFilterDto
    {
        $this->endDate = $endDate;

        return $this;
    }

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
        $nonEmptyIdentifiers = array_filter($identifiers, fn($value): bool => !is_null($value));

        if (count($nonEmptyIdentifiers) === 1) {
            $salesType = array_key_first($nonEmptyIdentifiers);
            return [
                'salesType' => SalesType::from($salesType),
                'salesTypeId' => $nonEmptyIdentifiers[$salesType],
            ];
        }

        return null;
    }
}