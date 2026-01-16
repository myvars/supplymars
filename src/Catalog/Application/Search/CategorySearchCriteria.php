<?php

namespace App\Catalog\Application\Search;

use App\Shared\Application\Search\SearchCriteria;
use Symfony\Component\Validator\Constraints as Assert;

final class CategorySearchCriteria extends SearchCriteria
{
    protected const array SORT_OPTIONS = ['id', 'name', 'defaultMarkup', 'isActive'];

    public ?string $priceModel = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Manager Id', min: 1, max: 1000000)]
    public ?int $managerId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Vat Rate Id', min: 1, max: 1000000)]
    public ?int $vatRateId = null;
}
