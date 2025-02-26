<?php

namespace App\DTO\SearchDto;

use Symfony\Component\Validator\Constraints as Assert;

final class SupplierProductSearchDto extends SearchDto implements SearchFilterInterface
{
    public const string SORT_DEFAULT = 'id';

    public const array SORT_OPTIONS = ['id', 'supplier.name', 'name', 'cost', 'stock', 'isActive'];

    public const string SORT_DIRECTION_DEFAULT = 'ASC';

    public const int LIMIT_DEFAULT = 5;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Supplier Id', min: 1, max: 1000000)]
    private ?int $supplierId = null;

    private ?string $productCode = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Supplier category Id', min: 1, max: 1000000)]
    private ?int $supplierCategoryId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Supplier Subcategory Id', min: 1, max: 1000000)]
    private ?int $supplierSubcategoryId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Supplier Manufacturer Id', min: 1, max: 1000000)]
    private ?int $supplierManufacturerId = null;

    private ?bool $inStock = null;

    private ?bool $isActive = null;

    #[\Override]
    public function getSearchParams(): array
    {
        $searchFilterParams = [
            'supplierId' => $this->supplierId,
            'productCode' => $this->productCode,
            'supplierCategoryId' => $this->supplierCategoryId,
            'supplierSubcategoryId' => $this->supplierSubcategoryId,
            'supplierManufacturerId' => $this->supplierManufacturerId,
            'inStock' => $this->inStock,
            'isActive' => $this->isActive,
        ];

        if (array_filter($searchFilterParams)) {
            $searchFilterParams['filter'] = 'on';
        }

        return array_merge($searchFilterParams, parent::getSearchParams());
    }

    public function getSupplierId(): ?int
    {
        return $this->supplierId;
    }

    public function setSupplierId(?int $supplierId): SupplierProductSearchDto
    {
        $this->supplierId = $supplierId;

        return $this;
    }

    public function getProductCode(): ?string
    {
        return $this->productCode;
    }

    public function setProductCode(?string $productCode): SupplierProductSearchDto
    {
        $this->productCode = $productCode;

        return $this;
    }

    public function getSupplierCategoryId(): ?int
    {
        return $this->supplierCategoryId;
    }

    public function setSupplierCategoryId(?int $supplierCategoryId): SupplierProductSearchDto
    {
        $this->supplierCategoryId = $supplierCategoryId;

        return $this;
    }

    public function getSupplierSubcategoryId(): ?int
    {
        return $this->supplierSubcategoryId;
    }

    public function setSupplierSubcategoryId(?int $supplierSubcategoryId): SupplierProductSearchDto
    {
        $this->supplierSubcategoryId = $supplierSubcategoryId;

        return $this;
    }

    public function getSupplierManufacturerId(): ?int
    {
        return $this->supplierManufacturerId;
    }

    public function setSupplierManufacturerId(?int $supplierManufacturerId): SupplierProductSearchDto
    {
        $this->supplierManufacturerId = $supplierManufacturerId;

        return $this;
    }

    public function getInStock(): ?bool
    {
        return $this->inStock;
    }

    public function setInStock(?bool $inStock): SupplierProductSearchDto
    {
        $this->inStock = $inStock;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): SupplierProductSearchDto
    {
        $this->isActive = $isActive;

        return $this;
    }
}
