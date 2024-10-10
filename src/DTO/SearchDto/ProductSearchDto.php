<?php

namespace App\DTO\SearchDto;

use Symfony\Component\Validator\Constraints as Assert;

final class ProductSearchDto extends SearchDto implements SearchFilterInterface
{
    public const SORT_DEFAULT = 'id';

    public const SORT_OPTIONS = ['id', 'name', 'cost', 'stock', 'sellPriceIncVat', 'isActive'];

    public const SORT_DIRECTION_DEFAULT = 'ASC';

    public const LIMIT_DEFAULT = 5;

    private ?string $mfrPartNumber = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Category Id', min: 1, max: 1000000)]
    private ?int $categoryId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Subcategory Id', min: 1, max: 1000000)]
    private ?int $subcategoryId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Manufacturer Id', min: 1, max: 1000000)]
    private ?int $manufacturerId = null;

    private ?bool $inStock = null;

    public function getSearchParams(): array
    {
        $searchFilterParams = [
            'mfrPartNumber' => $this->mfrPartNumber,
            'categoryId' => $this->categoryId,
            'subcategoryId' => $this->subcategoryId,
            'manufacturerId' => $this->manufacturerId,
            'inStock' => $this->inStock,
        ];

        if (array_filter($searchFilterParams)) {
            $searchFilterParams['filter'] = 'on';
        }

        return array_merge($searchFilterParams, parent::getSearchParams());
    }

    public function getMfrPartNumber(): ?string
    {
        return $this->mfrPartNumber;
    }

    public function setMfrPartNumber(?string $mfrPartNumber): ProductSearchDto
    {
        $this->mfrPartNumber = $mfrPartNumber;

        return $this;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function setCategoryId(?int $categoryId): ProductSearchDto
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function getSubcategoryId(): ?int
    {
        return $this->subcategoryId;
    }

    public function setSubcategoryId(?int $subcategoryId): ProductSearchDto
    {
        $this->subcategoryId = $subcategoryId;

        return $this;
    }

    public function getManufacturerId(): ?int
    {
        return $this->manufacturerId;
    }

    public function setManufacturerId(?int $manufacturerId): ProductSearchDto
    {
        $this->manufacturerId = $manufacturerId;

        return $this;
    }

    public function getInStock(): ?bool
    {
        return $this->inStock;
    }

    public function setInStock(?bool $inStock): ProductSearchDto
    {
        $this->inStock = $inStock;

        return $this;
    }
}