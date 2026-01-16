<?php

namespace App\Catalog\Application\Search;

use App\Shared\Application\Search\SearchCriteria;
use Symfony\Component\Validator\Constraints as Assert;

final class SubcategorySearchCriteria extends SearchCriteria
{
    protected const array SORT_OPTIONS = ['id', 'name', 'category.name', 'defaultMarkup', 'isActive'];

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Category Id', min: 1, max: 1000000)]
    public ?int $categoryId = null;

    public ?string $priceModel = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Manager Id', min: 1, max: 1000000)]
    public ?int $managerId = null;
}
