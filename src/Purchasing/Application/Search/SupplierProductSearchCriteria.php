<?php

namespace App\Purchasing\Application\Search;

use App\Shared\Application\Search\SearchCriteria;
use Symfony\Component\Validator\Constraints as Assert;

final class SupplierProductSearchCriteria extends SearchCriteria
{
    protected const array SORT_OPTIONS = ['id', 'supplier.name', 'name', 'cost', 'stock', 'isActive'];

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Supplier Id', min: 1, max: 1000000)]
    public ?int $supplierId = null;

    public ?string $productCode = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Supplier category Id', min: 1, max: 1000000)]
    public ?int $supplierCategoryId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Supplier Subcategory Id', min: 1, max: 1000000)]
    public ?int $supplierSubcategoryId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Supplier Manufacturer Id', min: 1, max: 1000000)]
    public ?int $supplierManufacturerId = null;

    public ?bool $inStock = null;

    public ?bool $isActive = null;
}
