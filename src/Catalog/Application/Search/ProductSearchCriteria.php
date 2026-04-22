<?php

declare(strict_types=1);

namespace App\Catalog\Application\Search;

use App\Shared\Application\Search\SearchCriteria;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductSearchCriteria extends SearchCriteria
{
    protected const array SORT_OPTIONS = ['id', 'name', 'cost', 'stock', 'sellPriceIncVat', 'isActive'];

    public ?string $mfrPartNumber = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Category Id', min: 1, max: 1000000)]
    public ?int $categoryId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Subcategory Id', min: 1, max: 1000000)]
    public ?int $subcategoryId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Manufacturer Id', min: 1, max: 1000000)]
    public ?int $manufacturerId = null;

    public ?bool $inStock = null;
}
