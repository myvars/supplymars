<?php

namespace App\DTO\SearchDto;

use Symfony\Component\Validator\Constraints as Assert;

final class SubcategorySearchDto extends SearchDto implements SearchFilterInterface
{
    public const SORT_DEFAULT = 'id';

    public const SORT_OPTIONS = ['id', 'name', 'category.name', 'defaultMarkup', 'isActive'];

    public const SORT_DIRECTION_DEFAULT = 'ASC';

    public const LIMIT_DEFAULT = 5;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Category Id', min: 1, max: 1000000)]
    private ?int $categoryId = null;

    public function getSearchParams(): array
    {
        $searchFilterParams = [
            'categoryId' => $this->categoryId,
        ];

        if (array_filter($searchFilterParams)) {
            $searchFilterParams['filter'] = 'on';
        }

        return array_merge($searchFilterParams, parent::getSearchParams());
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function setCategoryId(?int $categoryId): SubcategorySearchDto
    {
        $this->categoryId = $categoryId;

        return $this;
    }
}