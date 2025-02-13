<?php

namespace App\DTO;

use App\Enum\SalesDuration;
use App\Enum\ProductSalesMetric;
use App\Enum\SalesType;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductSalesReportDto
{
    public const int LIMIT_DEFAULT = 10;

    private string $sortDirection = 'desc';

    private ProductSalesMetric $sort = ProductSalesMetric::QTY;

    private SalesDuration $duration = SalesDuration::LAST_30;

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

    public function getSort(): ?ProductSalesMetric
    {
        return $this->sort;
    }

    public function setSort(?string $sort): ProductSalesReportDto
    {
        if (!ProductSalesMetric::isValid($sort)) {
            $sort = ProductSalesMetric::default()->value;
        }

        $this->sort = ProductSalesMetric::from($sort);

        return $this;
    }

    public function getSortDirection(): ?string
    {
        return $this->sortDirection;
    }

    public function setSortDirection(?string $sortDirection): ProductSalesReportDto
    {
        if (!in_array(strtoupper((string) $sortDirection), ['ASC', 'DESC'])) {
            $sortDirection = 'DESC';
        }

        $this->sortDirection = strtolower((string) $sortDirection);

        return $this;
    }

    public function getDuration(): ?SalesDuration
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): ProductSalesReportDto
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

    public function setProductId(?int $productId): ProductSalesReportDto
    {
        $this->productId = $productId;

        return $this;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function setCategoryId(?int $categoryId): ProductSalesReportDto
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function getSubcategoryId(): ?int
    {
        return $this->subcategoryId;
    }

    public function setSubcategoryId(?int $subcategoryId): ProductSalesReportDto
    {
        $this->subcategoryId = $subcategoryId;

        return $this;
    }

    public function getManufacturerId(): ?int
    {
        return $this->manufacturerId;
    }

    public function setManufacturerId(?int $manufacturerId): ProductSalesReportDto
    {
        $this->manufacturerId = $manufacturerId;

        return $this;
    }

    public function getSupplierId(): ?int
    {
        return $this->supplierId;
    }

    public function setSupplierId(?int $supplierId): ProductSalesReportDto
    {
        $this->supplierId = $supplierId;

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

        if ($nonEmptyIdentifiers === []) {
            return [
                'salesType' => SalesType::ALL,
                'salesTypeId' => 1,
            ];
        }

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